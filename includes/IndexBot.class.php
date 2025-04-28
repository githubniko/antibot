<?php

namespace WAFSystem;

class IndexBot
{
    private $Logger;
    private $rulesFile;

    public function __construct(Config $config, Logger $logger)
    {
        $this->Logger = $logger;

        $file = ltrim($config->get('lists', 'indexbot_rules'), "/\\");
        if ($file == null) {
            $file = "lists/indexbot.rules";
        }
        $this->rulesFile = $config->BasePath . $file;

        if (!file_exists($this->rulesFile)) {
            $this->createDefaultRulesFile();
        }
    }

    # Функция проверяет ip на индексирующего бота
    public function isIndexbot($client_ip)
    {
        $isIPv6 = filter_var($client_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

        // Выполняем обратный DNS-запрос
        $hostname = gethostbyaddr($client_ip);
        $this->Logger->log('PTR: ' . $hostname);

        if (!file_exists($this->rulesFile)) {
            return false;
        }

        $file = fopen($this->rulesFile, 'r');
        if (!$file) {
            $this->Logger->log("Failed to open rules file: " . $this->rulesFile);
            return false;
        }

        try {
            while (($line = fgets($file)) !== false) {
                $line = trim($line);
                if (empty($line)) continue;

                $reg = trim(mb_eregi('(.*)(#.*)', $line, $match) ? $match[1] : $line);
                if (empty($reg)) continue;

                if (!$this->validateDomain($reg)) {
                    $this->Logger->log('The domain is not valid: ' . $reg);
                    continue;
                }
                $reg = str_replace('.', '\.', $reg);

                mb_regex_encoding('UTF-8');   //кодировка строки

                // Проверяем, заканчивается ли доменное имя на .googlebot.com или .google.com
                $count = preg_match("/\.$reg$/i", $hostname, $match);   //поиск подстрок в строке pValue
                if ($count > 0) {
                    // Выполняем прямой DNS-запрос в зависимости от типа IP
                    $resolvedRecords = dns_get_record($hostname, $isIPv6 ? DNS_AAAA : DNS_A);

                    // Проверяем, совпадает ли исходный IP с одним из разрешенных
                    if (!empty($resolvedRecords)) {
                        foreach ($resolvedRecords as $record) {
                            if ($isIPv6) {
                                if (isset($record['ipv6']) && $record['ipv6'] === $client_ip) {
                                    fclose($file);
                                    return true;
                                }
                            } else {
                                if (isset($record['ip']) && $record['ip'] === $client_ip) {
                                    fclose($file);
                                    return true;
                                }
                            }
                        }
                    }
                    fclose($file);
                    return false;
                }
            }
        } finally {
            fclose($file);
        }
        return false;
    }

    private function validateDomain($domain)
    {
        $pattern = '/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/';
        return preg_match($pattern, $domain);
    }

    /**
     * Создает файл правил по умолчанию
     */
    private function createDefaultRulesFile()
    {
        $defaultContent = <<<EOT
# Список PTR индексирующих роботов. Указывается домен первого уровня.
# IP-адреса автоматически добавляются в whilelist для улучшения производительности.

yandex.ru
googlebot.com
google.com
yandex.ru
yandex.net
yandex.com
msn.com         # Поисковыик bing.com +http://www.bing.com/bingbot.htm
petalsearch.com # Сервисы Huawei +https://webmaster.petalsearch.com/site/petalbot
apple.com       # http://www.apple.com/go/applebot
baidu.com       # Китайский поисковик +http://www.baidu.com/search/spider.html
baidu.jp        # Китайский поисковик +http://www.baidu.com/search/spider.html
telegram.org    # Телеграм бот, для чтения мета 
odnoklassniki.ru # Для чтения метатегов
mail.ru         # Сервисы mail.ru, vk.com
googleusercontent.com # Discord +https://discordapp.com

EOT;

        if (!file_put_contents($this->rulesFile, $defaultContent)) {
            $this->Logger->log("Failed to create default Indexbot rules file: " . $this->rulesFile);
        }
    }
}
