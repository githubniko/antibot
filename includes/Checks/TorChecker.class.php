<?php
namespace WAFSystem;

class TorChecker extends ListBase
{
    public $listName = 'blacklist_tor';
    private $cacheTime; // int Время жизни кэша в секундах (по умолчанию 3600)
    private $timeout = 2; // int Таймаут запроса в секундах (по умолчанию 2)
    private $url = 'https://www.dan.me.uk/torlist/?exit'; // Список загружаемых листов для HTTP-метода
    public $enabled = false;
    public $action = 'BLOCK';

    public function __construct(Config $config, Logger $logger, $cacheTime = 3600)
    {
        $this->Config = $config;
        $this->cacheTime = $cacheTime;

        $this->enabled = $this->Config->init('tor_checks', 'enabled', $this->enabled, 'блокировать вход с ip tor-сетей');
        $this->action = $this->Config->init('tor_checks', 'action', $this->action, 'CAPTCHA - капча, BLOCK - заблокировать, SKIP - пропустить правило');
        $this->url = $this->Config->init('tor_checks', 'url', $this->url, 'список загружаемых листов для HTTP-метода');
        $this->timeout = $this->Config->init('tor_checks', 'timeout', $this->timeout, 'таймаут ожидания ответа в секундах');
        $this->cacheTime = $this->Config->init('tor_checks', 'cache_time', $this->cacheTime, 'секунд, интервал обновления списка');


        $file = ltrim($config->get('lists', $this->listName, ''), "/\\");
        if (empty($file)) {
            $file = "lists/" . $this->listName;
            $config->set('lists', $this->listName, $file);
        }

        parent::__construct($file, $config, $logger);
    }

    protected function createDefaultFileContent()
    {
        # Загружаем начальную версию TOR-адресов
        try {
            $defaultContent = $this->DownloadList();
        } catch (\Exception $e) {
            $this->Logger->log($e->getMessage(), [static::class]);
            $defaultContent = <<<EOT
# TOR список IP-адресов (автоматическое обновление)

EOT;
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
        if (!$this->Config->get('checks', 'tor', false))
            return;

        $result = false;

        # Обновления списка TOR-адресов
        try {
            if (!file_exists($this->absolutePath)) {
                # Создаем файл листа, если не существует
                $this->createDefaultFileContent();
                $this->initListFile();
            } else {
                # Пересоздаем, если устарел
                $cacheTime = filemtime($this->absolutePath);

                if (time() - $cacheTime > $this->cacheTime) {
                    $this->createDefaultFileContent();
                    $this->initListFile();
                }
            }
            $result = $this->isListed($ip);
        } catch (\Exception $e) {
            $this->Logger->log("HTTP method error", [static::class]);
            # Пробуем DNS метод, есть HTTP не сработал
            try {
                $result = $this->checkViaDns($ip, $this->timeout);
            } catch (\Exception $e) {
                throw new \Exception("All Tor check methods failed: (" . $e->getMessage() . ")");
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

            try {
                $records = dns_get_record($dnsQuery, DNS_A);

                if (!empty($records) && isset($records[0]['ip']) && $records[0]['ip'] === '127.0.0.2') {
                    return true;
                }
            } catch (\Exception $e) {
                throw "Error: DNS is not available ";
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
        return $Curl->fetch($this->url);
    }
}
