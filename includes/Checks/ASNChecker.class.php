<?php

namespace WAFSystem;

use Exception;

class ASNChecker extends ListBase
{
    public $listName = 'blacklist_asn';
    public $enabled = false;
    public $action = 'CAPTCHA';
    private $url = 'https://raw.githubusercontent.com/ipverse/as-ip-blocks/master/as/'; // база ASN-IP https://github.com/ipverse/as-ip-blocks
    private $updateTime = 604800; // int Время опроса базы ASN-IP (по умолчанию 7 дней)
    private $timeout = 3; // int Таймаут запроса в секундах (по умолчанию 3)
    private $cachePath = null; // директория для хранения временных файлов
    private $dbPath; // путь до файла базы данных SQLite
    private $db = null; // указатель на кеш-базу данных

    private $modulName = 'asn_checker';

    public function __construct(Config $config, Logger $logger, $params = [])
    {
        $this->Logger = $logger;

        # Заменяет переменные класса, указанные в $params[]
        if (sizeof($params) > 0) {
            foreach ($params as $key => $value) {
                if (isset($this->{$key})) {
                    if (empty($value))
                        throw new Exception($key . ' cannot be empty.');
                    $this->{$key} = $value;
                }
            }
        }

        $this->enabled = $config->init($this->modulName, 'enabled', $this->enabled);

        $listName = $config->get($this->modulName, $this->listName); // запрашивает путь к файлу со списком
        $file = ltrim($listName, "/\\");
        if ($listName === NULL) { // Если в конфиге нет параметра, то создаем
            $file = "lists/" . $this->listName;
            $config->set($this->modulName, $this->listName, $file, 'список');
        }

        if (empty($listName)) { // Если параметр пуст, то отключаем проверку по списку
            $this->enabled = false;
            return;
        }

        $url = $config->init($this->modulName, 'url', $this->url, 'база ASN-IP https://github.com/ipverse/as-ip-blocks');
        if ($url == "https://raw.githubusercontent.com/ipverse/as-ip/master/as/") // замена устаревшей переменной
            $url = $config->set($this->modulName, 'url', $this->url, 'база ASN-IP https://github.com/ipverse/as-ip-blocks');

        $this->url = $url;
        $this->timeout = $config->init($this->modulName, 'timeout', $this->timeout, 'таймаут ожидания ответа в секундах');
        $this->updateTime = $config->init($this->modulName, 'updateTime', $this->updateTime, 'время опроса базы ASN-IP в секундах');

        if (!$this->enabled) return; // выходим, если модуль выключен

        $this->cachePath = $config->CachePath . '';
        $this->dbPath = $this->cachePath . $this->listName . '.db';

        $this->db = new \Utility\SQLiteWrapper($this->dbPath, $logger);

        parent::__construct($file, $config, $logger);

        # если файл удален внучную и таблица стерлась, то создаем заново
        $tableCheck = $this->db->query("SELECT name FROM sqlite_master WHERE name='ip_asn_v4' OR name='ip_asn_v6'");
        if ($tableCheck === false || $tableCheck->fetchArray() === false) {
            $this->eventInitListFile();
        }
    }

    protected function eventInitListFile()
    {
        $this->Logger->log("Create database $this->dbPath", get_class($this));
        if ($this->Lock->Lock()) {
            try {
                if (!is_file($this->dbPath)) {
                    $msg = 'Failed to create database. Check folder permissions: ' . $this->cachePath;
                    $this->Logger->log($msg, get_class($this));
                    throw new \Exception($msg);
                }

                // Создаем таблицу для IPv4
                $this->db->exec('
                        CREATE TABLE IF NOT EXISTS ip_asn_v4 (
                            ip_beg BLOB NOT NULL,
                            ip_end BLOB NOT NULL,
                            PRIMARY KEY (ip_beg, ip_end)
                        ) WITHOUT ROWID;
                    ');

                // Создаем таблицу для IPv6
                $this->db->exec('
                        CREATE TABLE IF NOT EXISTS ip_asn_v6 (
                            ip_beg BLOB NOT NULL,
                            ip_end BLOB NOT NULL,
                            PRIMARY KEY (ip_beg, ip_end)
                        ) WITHOUT ROWID;
                    ');

                $this->db->exec('CREATE INDEX IF NOT EXISTS idx_ipv4_range ON ip_asn_v4 (ip_beg, ip_end)');
                $this->db->exec('CREATE INDEX IF NOT EXISTS idx_ipv6_range ON ip_asn_v6 (ip_beg, ip_end)');
                # Устанавливаем одинаковое время модификации

                touch($this->absolutePath, filemtime($this->dbPath));
            } catch (\Exception $e) {
                $this->Lock->Unlock();
                throw $e;
            }
            $this->Lock->Unlock();
        }
    }

    protected function createDefaultFileContent()
    {
        $defaultContent = <<<EOT
# {$this->listName}
# Формат: 
#  ASN # комментарий

EOT;
        return $defaultContent;
    }

    protected function validate($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    public function Checking($ip)
    {
        $this->Lock->waitForUnlock(); // ждем разрешение класса блокировки процесса

        if ($this->validate($ip) === false) {
            $msg = "Not valid ip address";
            $this->Logger->log($msg, get_class($this));
            throw new \Exception($msg);
        }
        $updateTimeDB = filemtime($this->dbPath);
        $updateTimeList = filemtime($this->absolutePath);
        if ($updateTimeDB === false)
            $this->Logger->log("Error getting filetime (" . $this->dbPath . ")", get_class($this));

        if ($updateTimeList === false)
            $this->Logger->log("Error getting filetime (" . $this->absolutePath . ")", get_class($this));

        if ($updateTimeList > $updateTimeDB) { // обновляем базу, если изменился список ASN
            $this->Logger->log("ASN list modified (" . $this->path . ")", get_class($this));
            $this->updateCacheDB();
        } elseif (time() - $updateTimeDB > $this->updateTime) { // если кеш устарел
            $this->Logger->log("Cache ASN outdated", get_class($this));
            $this->updateCacheDB();
        }

        // Определяем тип IP
        $is_ipv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        $ip_bin = $is_ipv6 ? inet_pton($ip) : pack('N', ip2long($ip));
        $table = $is_ipv6 ? 'ip_asn_v6' : 'ip_asn_v4';

        $result = $this->db->query("
            SELECT 1 FROM $table 
            WHERE :ip >= ip_beg AND :ip <= ip_end
            LIMIT 1
        ", [':ip' => [$ip_bin, SQLITE3_BLOB]]);

        return $result->fetchArray() !== false;
    }

    private function updateCacheDB()
    {
        $this->Logger->log("Start ASN update from: " . $this->url, get_class($this));
        if ($this->Lock->Lock()) {
            try {
                $countASN = $countNetwork = 0;
                $arrASN = [];
                $arrNetwork = [];

                $this->db->exec('DELETE FROM ip_asn_v4');
                $this->db->exec('DELETE FROM ip_asn_v6');

                $arr = $this->readToArray(); // читаем список ASN                
                if (sizeof($arr) > 0) {
                    foreach ($arr as $value) {
                        if (!\Utility\Network::validateASN($value)) {
                            $this->Logger->log("Invalid value ASN: $value", get_class($this));
                            continue;
                        }

                        // Удаляем префикс "AS" если есть
                        if (strpos($value, 'AS') === 0) {
                            $value = substr($value, 2);
                        }

                        # Загружаем сети по номеру ASN из github
                        $Curl = new \Utility\Curl($this->timeout);
                        $url = $this->url . $value . "/aggregated.json";
                        $res = $Curl->fetch($url);
                        if ($res) {
                            # Вносим данные в кеш-базу
                            $obj = json_decode($res);
                            if ($obj === null || !isset($obj->prefixes)) {
                                $this->Logger->log("Invalid JSON or missing 'subnets' in response: $res", get_class($this));
                                continue;
                            }

                            if (sizeof($obj->prefixes->ipv4) > 0) {
                                foreach ($obj->prefixes->ipv4 as $cidr) {
                                    if (\Utility\Network::isValidCidr($cidr) == false) // Валидация входящих данных
                                        continue;

                                    $this->addToDB($cidr);

                                    # Сбор информации для логирования
                                    $countNetwork++;
                                    array_push($arrNetwork, $cidr);
                                }
                            }
                            if (sizeof($obj->prefixes->ipv6) > 0) {

                                foreach ($obj->prefixes->ipv6 as $cidr) {
                                    if (\Utility\Network::isValidCidr($cidr) == false) // Валидация входящих данных
                                        continue;

                                    $this->addToDB($cidr);

                                    # Сбор информации для логирования
                                    $countNetwork++;
                                    array_push($arrNetwork, $cidr);
                                }
                            }
                            array_push($arrASN, 'AS' . $value);
                            $countASN++;
                        }
                    }
                }
                $this->Logger->log("Update database, ASN: " . $countASN . " Networks: " . $countNetwork, get_class($this));
                if ($countASN > 0 || $countNetwork > 0) {
                    $this->Logger->log("" . implode(", ", $arrASN) . "\n" . implode("\n", $arrNetwork), get_class($this));
                }
            } catch (\Exception $e) {
                $this->Logger->log("Error update database: " . $e->getMessage(), get_class($this));
            }

            # Устанавливаем такое же время модификации как и файл листа
            $new_time = filemtime($this->dbPath);
            touch($this->absolutePath, $new_time, $new_time);

            $this->Lock->Unlock();
        }
    }

    private function addToDB($ipOrCidr)
    {
        $range = \Utility\Network::ipToRange($ipOrCidr); // Преобразуем IP/CIDR в диапазон

        $table = $range['is_ipv6'] ? 'ip_asn_v6' : 'ip_asn_v4';

        $result = $this->db->query("
        INSERT OR IGNORE INTO $table 
        (ip_beg, ip_end) 
        VALUES (:ip_beg, :ip_end)
    ", [
            ':ip_beg' => [$range['beg'], SQLITE3_BLOB],
            ':ip_end' => [$range['end'], SQLITE3_BLOB]
        ]);

        return $result;
    }
}
