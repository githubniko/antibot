<?php

namespace WAFSystem;

class TorChecker extends ListBase
{
    public $listName = 'blacklist_tor';

    private $modulName = 'tor_checks';
    private $cacheTime = 86400; // int Время жизни кэша в секундах (по умолчанию 1 сутки)
    private $timeout = 2; // int Таймаут запроса в секундах (по умолчанию 2)
    private $url = 'https://www.dan.me.uk/torlist/?exit'; // Список загружаемых листов для HTTP-метода
    public $enabled = false;
    public $action = 'BLOCK';


    public function __construct(Config $config, Logger $logger)
    {
        $this->enabled = $config->init($this->modulName, 'enabled', $this->enabled, 'блокировать вход с ip tor-сетей');
        $this->action = $config->init($this->modulName, 'action', $this->action, 'CAPTCHA - капча, BLOCK - заблокировать, SKIP - пропустить правило');
        $this->url = $config->init($this->modulName, 'url', $this->url, 'список загружаемых листов для HTTP-метода');
        $this->timeout = $config->init($this->modulName, 'timeout', $this->timeout, 'таймаут ожидания ответа в секундах');
        $this->cacheTime = $config->init($this->modulName, 'cache_time', $this->cacheTime, 'секунд, интервал обновления списка');

        $file = ltrim($config->get($this->modulName, $this->listName, ''), "/\\");
        if (empty($file)) {
            $file = "lists/" . $this->listName;
            $config->set($this->modulName, $this->listName, $file);
        }

        parent::__construct($file, $config, $logger);
    }

    protected function createDefaultFileContent()
    {
        # Загружаем списки TOR
        try {
            $defaultContent = $this->DownloadList();
        } catch (\Exception $e) {
            throw $e;
        }

        return $defaultContent;
    }

    protected function validate($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    protected function Comparison($value1, $value2)
    {
        if (inet_pton($value1) === inet_pton($value2))
            return true;
        return false;
    }

    public function isTor($ip)
    {
        $result = false;

        # Обновления списка TOR-адресов
        $cacheTime = @filemtime($this->absolutePath);
        $isFile = is_file($this->absolutePath);

        try {
            if (
                !$isFile || // для первого запуска
                ($isFile && time() - $cacheTime > $this->cacheTime) // для обновления
            ) {
                $this->createDefaultFileContent();
                $this->saveListFile();
            }
        } catch (\Exception $e) {
            $this->Logger->log("HTTP method error: " . $e->getMessage(), [static::class]);
            $new_time = time();
            touch($this->absolutePath, $new_time, $new_time); // изменяем время файла, чтобы не было частых обращений к серверу списков
        }

        # Если файл содержит данные, то проверяем по нему
        if (is_file($this->absolutePath) && filesize($this->absolutePath) > 200) { // Если файл пуст, то
            $result = $this->isListed($ip);
        } else {
            # Пробуем DNS метод
            try {
                $result = $this->checkViaDns($ip, $this->timeout);
            } catch (\Exception $e) {
                $this->Logger->log("All Tor check methods failed: (" . $e->getMessage() . ")");
            }
        }

        return $result;
    }

    /**
     * Проверка через DNS (IPv4)
     */
    private function checkViaDns($ip, $timeout)
    {
        $isIPv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
        if ($isIPv6)
            throw new \Exception("Unable to check IPv6 via DNS method");

        // Настройка таймаута
        $originalTimeout = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', $timeout);

        try {
            $reversedIp = implode('.', array_reverse(explode('.', $ip)));
            $dnsQuery = $reversedIp . '.dnsel.torproject.org';

            $cacheDir = $this->Config->CachePath . 'dnstor';
            $driver = new \DnsCache\FileCacheDriver($cacheDir);
            $Dns = new \DnsCache\DnsCache($driver);

            try {
                $records = $Dns->getRecord($dnsQuery, DNS_A);

                if (!empty($records) && isset($records[0]['ip']) && $records[0]['ip'] === '127.0.0.2') {
                    return true;
                }
            } catch (\Exception $e) {
                throw $e;
                // throw new Exception("Error: DNS is not available ");
            }

            return false;
        } finally {
            ini_set('default_socket_timeout', $originalTimeout);
        }
    }

    /**
     * Загружает лист 
     */
    private function DownloadList()
    {
        $Curl = new \Utility\Curl($this->timeout);
        $res = $Curl->fetch($this->url);
        return $res;
    }
}
