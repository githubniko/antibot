<?php

namespace WAFSystem;

class ASNChecker extends ListBase
{
    public $listName = 'blacklist_asn';
    public $enabled = true;
    public $action = 'CAPTCHA';
    private $url = 'https://raw.githubusercontent.com/ipverse/asn-ip/master/as/'; // база ASN-IP https://github.com/ipverse/asn-ip
    private $updateTime = 86400; // int Время опроса базы ASN-IP (по умолчанию 1 сутки)
    private $timeout = 2; // int Таймаут запроса в секундах (по умолчанию 2)
    private $cacheDir = null; // директория для хранения временных файлов
    private $dbPath; // путь до файла базы данных SQLite
    private $db = null; // указатель на кеш-базу данных

    private $modulName = 'asn_checker';

    public function __construct(Config $config, Logger $logger)
    {
        $this->enabled = $config->init($this->modulName, 'enabled', $this->enabled);
        $this->action  = $config->init($this->modulName, 'action', $this->action, 'CAPTCHA - капча, BLOCK - заблокировать, SKIP - ничего не делать');

        $file = ltrim($config->get($this->modulName, $this->listName, ''), "/\\");
        if (empty($file)) {
            $file = "lists/" . $this->listName;
            $config->set($this->modulName, $this->listName, $file, 'список');
        }

        $this->url = $config->init($this->modulName, 'url', $this->url, 'база ASN-IP');
        $this->timeout = $config->init($this->modulName, 'timeout', $this->timeout, 'таймаут ожидания ответа в секундах');
        $this->updateTime = $config->init($this->modulName, 'updateTime', $this->updateTime, 'время опроса базы ASN-IP в секундах');

        parent::__construct($file, $config, $logger);

        $this->cacheDir = $this->Config->CachePath . '';
        $this->dbPath = $this->cacheDir . $this->listName . '.db';

        $this->db = $this->getDBConnection(); // Инициализация DB
    }

    protected function eventInitListFile()
    {
        if (function_exists('sqlite_open')) {
            $msg = 'Sqlite PHP extension loaded';
            $this->Logger->log($msg, static::class);
            throw new \Exception($msg);
        }

        $this->Lock->Lock();
        try {
            $this->db->exec('
                CREATE TABLE ip_asn (
                        is_ipv6 BOOLEAN NOT NULL,
                        ip_beg BLOB NOT NULL,  -- Храним как 16-байтовый blob для IPv6
                        ip_end BLOB NOT NULL,
                        PRIMARY KEY (is_ipv6, ip_beg, ip_end)
                    ) WITHOUT ROWID;
            ');

            // Индексы для обоих типов адресов
            $this->db->exec('CREATE INDEX IF NOT EXISTS idx_ipv4_range ON ip_asn (ip_beg, ip_end) WHERE is_ipv6 = 0');
            $this->db->exec('CREATE INDEX IF NOT EXISTS idx_ipv6_range ON ip_asn (ip_beg, ip_end) WHERE is_ipv6 = 1');

            if (!is_file($this->dbPath)) {
                $msg = 'Failed to create database. Check folder permissions: ' . $this->cacheDir;
                $this->Logger->log($msg, static::class);
                throw new \Exception($msg);
            }

            # Устанавливаем одинаковое время модификации
            $new_time = filemtime($this->dbPath);
            touch($this->absolutePath, $new_time, $new_time);
        } finally {
            $this->Lock->Unlock();
        }
    }

    protected function createDefaultFileContent()
    {
        $defaultContent = <<<EOT
# Черный список ASN
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
        if ($this->validate($ip) === false) {
            $msg = "Not valid ip address";
            $this->Logger->log($msg, static::class);
            throw new \Exception($msg);
        }

        # Создаем таблицу
        $tableCheck = $this->db->query("SELECT name FROM sqlite_master WHERE name='ip_asn'");
        if ($tableCheck->fetchArray() === false) {
            $this->eventInitListFile();
        }

        $updateTimeDB = filemtime($this->dbPath);
        $updateTimeList = filemtime($this->absolutePath);

        if ($updateTimeList > $updateTimeDB) { // обновляем базу, если изменился список ASN
            $this->Logger->log("ASN list modified (" . $this->path . ")", static::class);
            $this->updateCacheDB();
        } elseif (time() - $updateTimeDB > $this->updateTime) { // если кеш устарел
            $this->Logger->log("Cache ASN outdated", static::class);
            $this->updateCacheDB();
        }

        // Определяем тип IP
        $is_ipv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        $ip_bin = $is_ipv6 ? inet_pton($ip) : pack('N', ip2long($ip));

        $stmt = $this->db->prepare('
            SELECT 1 FROM ip_asn 
            WHERE is_ipv6 = :is_ipv6
            AND :ip >= ip_beg 
            AND :ip <= ip_end
            LIMIT 1
        ');

        $stmt->bindValue(':is_ipv6', $is_ipv6, SQLITE3_INTEGER);
        $stmt->bindValue(':ip', $ip_bin, SQLITE3_BLOB);

        return $stmt->execute()->fetchArray() !== false;
    }

    private function updateCacheDB()
    {
        $this->Lock->Lock();
        $this->Logger->log("Start ASN update", static::class);

        # Пересоздаем базу данных
        rename($this->dbPath, $this->dbPath . '.back');
        $this->db = $this->getDBConnection();
        $this->eventInitListFile();

        try {
            $countASN = $countNetwork = 0;
            $arrASN = [];
            $arrNetwork = [];

            $arr = $this->readToArray(); // читаем список ASN
            if (sizeof($arr) > 0) {
                foreach ($arr as $value) {
                    if (!\Utility\Network::validateASN($value)) {
                        $this->Logger->log("Invalid value ASN: $value", static::class);
                        continue;
                    }

                    // Удаляем префикс "AS" если есть
                    if (strpos($value, 'AS') === 0) {
                        $value = substr($value, 2);
                    }

                    # Загружаем сети по номеру ASN
                    $Curl = new \Utility\Curl($this->timeout);
                    $url = $this->url . $value . "/aggregated.json";
                    $res = $Curl->fetch($url);
                    if ($res) {
                        # Вносим данные в кеш-базу
                        $obj = json_decode($res);
                        if (sizeof($obj->subnets->ipv4) > 0) {
                            foreach ($obj->subnets->ipv4 as $cidr) {
                                $this->addToDB($cidr);
                                $countNetwork++;
                                array_push($arrNetwork, $cidr);
                            }
                        }
                        if (sizeof($obj->subnets->ipv6) > 0) {
                            foreach ($obj->subnets->ipv6 as $cidr) {
                                $this->addToDB($cidr);
                                $countNetwork++;
                                array_push($arrNetwork, $cidr);
                            }
                        }
                        array_push($arrASN, 'AS' . $value);
                        $countASN++;
                    }
                }
            }
            $this->Logger->log("Update database, ASN: " . $countASN . " Networks: " . $countNetwork, static::class);
            if ($countASN > 0 || $countNetwork > 0) {
                $this->Logger->log("" . implode(", ", $arrASN) . "\n" . implode("\n", $arrNetwork), static::class);
            }
        } catch (\Exception $e) {
            $this->Logger->log($e->getMessage(), static::class);
            rename($this->dbPath . '.back', $this->dbPath);
        } finally {
            @unlink($this->dbPath . '.back');

            # Устанавливаем такое же время модификации как и файл листа
            $new_time = filemtime($this->dbPath);
            touch($this->absolutePath, $new_time, $new_time);

            $this->Logger->log("End ASN update", static::class);
            $this->Lock->Unlock();
        }
    }

    private function addToDB($ipOrCidr)
    {
        $range = \Utility\Network::ipToRange($ipOrCidr); // Преобразуем IP/CIDR в диапазон

        $stmt = $this->db->prepare('
            INSERT OR IGNORE INTO ip_asn 
            (is_ipv6, ip_beg, ip_end) 
            VALUES (:is_ipv6, :ip_beg, :ip_end)
        ');

        $stmt->bindValue(':is_ipv6', $range['is_ipv6'], SQLITE3_INTEGER);
        $stmt->bindValue(':ip_beg', $range['beg'], SQLITE3_BLOB);
        $stmt->bindValue(':ip_end', $range['end'], SQLITE3_BLOB);

        return $stmt->execute();
    }

    private function getDBConnection()
    {
        $db = new \SQLite3($this->dbPath);
        // Оптимизации для производительности
        $db->exec('PRAGMA journal_mode = WAL');
        $db->exec('PRAGMA synchronous = NORMAL');
        $db->exec('PRAGMA temp_store = MEMORY');
        return $db;
    }
}
