<?php

namespace WAFSystem;

use Exception;

class IndexBot extends ListBase
{
    public $listName = 'indexbot_rules';
    public $enabled = true;

    private $Profile;
    private $Dns;
    private $modulName = 'indexbot_checker';
    
    public function __construct(Config $config, Profile $profile, Logger $logger)
    {
        $this->Logger = $logger;
        $this->Profile = $profile;

        $this->enabled = $config->init($this->modulName, 'enabled', $this->enabled, 'пропускать поисковых ботов');

        $file = ltrim($config->get($this->modulName, $this->listName, ''), "/\\");
        if (empty($file)) {
            $file = "lists/" . $this->listName;
            $config->set($this->modulName, $this->listName, $file);
        }

        $cacheDir = $config->CachePath . 'dns';
        $driver = new \Cache\FileCacheDriver($cacheDir);
        $this->Dns = new \Cache\DnsCache($driver);
        parent::__construct($file, $config, $logger);
    }

    # Функция проверяет ip на индексирующего бота
    public function Checking($client_ip)
    {
        try {
            $hostname = $this->Dns->getHostByAddr($client_ip); // Выполняем обратный DNS-запрос
        } catch (\Exception $e) {
            $this->Logger->log($e->getMessage(), [static::class]);
            throw new Exception($e->getMessage());
        }
        $this->Logger->log('PTR: ' . $hostname);
        return $this->isListed($hostname);
    }

    protected function validate($domain)
    {
        // Проверка на домен
        $pattern = '/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/';
        if(preg_match($pattern, $domain))
            return true;

        // Проверка на IP-адрес
        if(filter_var($domain, FILTER_VALIDATE_IP) !== false)
            return true;
        
        return false;
    }

    protected function createDefaultFileContent()
    {
        $defaultContent = <<<EOT
# {$this->listName}
# Список PTR индексирующих роботов. Указывается домен первого уровня.

yandex.ru
googlebot.com
google.com
googlezip.net   # Chrome Privacy Preserving Prefetch Proxy https://foxi.biz/viewtopic.php?id=294
yandex.ru
yandex.net
yandex.com
msn.com         # Поисковик bing.com +http://www.bing.com/bingbot.htm
bing.com        # Поисковик bing.com +http://www.bing.com/bingbot.htm
msedge.net      # Запросы Edge
petalsearch.com # Сервисы Huawei +https://webmaster.petalsearch.com/site/petalbot
apple.com       # http://www.apple.com/go/applebot
baidu.com       # Китайский поисковик +http://www.baidu.com/search/spider.html
baidu.jp        # Китайский поисковик +http://www.baidu.com/search/spider.html
telegram.org    # Телеграм бот, для чтения мета 
odnoklassniki.ru # Для чтения метатегов
mail.ru         # Сервисы mail.ru, vk.com
googleusercontent.com # Discord +https://discordapp.com
letsencrypt.org # Бесплатные SSL-сертификаты
duckduckgo.com  # DuckDuckGo-Favicons-Bot
w3.org # https://validator.w3.org/

EOT;
        return $defaultContent;
    }

    protected function Comparison($value1, $hostname)
    {
        $reg = str_replace('.', '\.', $value1);
        mb_regex_encoding('UTF-8');   //кодировка строки

        if (preg_match("/\.$reg$/i", $hostname, $match) === 1) {
            try {
                // Выполняем прямой DNS-запрос в зависимости от типа IP
                $resolvedRecords = $this->Dns->getRecord($hostname, $this->Profile->isIPv6 ? DNS_AAAA : DNS_A);
            } catch (\Exception $e) {
                $this->Logger->log($e->getMessage(), [static::class]);
                throw new Exception($e->getMessage());
            }

            // Проверяем, совпадает ли исходный IP с одним из разрешенных
            if (!empty($resolvedRecords)) {
                foreach ($resolvedRecords as $record) {
                    if ($this->Profile->isIPv6) {
                        if (isset($record['ipv6']) && inet_pton($record['ipv6']) === inet_pton($this->Profile->IP)) {
                            return true;
                        }
                    } else {
                        if (isset($record['ip']) && inet_pton($record['ip']) === inet_pton($this->Profile->IP)) {
                            return true;
                        }
                    }
                }
            }
            return false;
        }
        return false;
    }
}
