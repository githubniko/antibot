<?php

namespace WAFSystem;

class IPWhitelist extends IPList
{
    public function __construct(Config $config, Logger $logger)
    {

        $file = ltrim($config->get('lists', 'whitelist_ip'), "/\\");
        if ($file == null) {
            $logfile = "lists/whitelist_ip";
        }
        $fullPathFile = $config->BasePath . $file;

        parent::__construct($fullPathFile, $logger);
    }

    protected function eventInitListFile()
    {
        # добавляем в исключения ip серверов сайта
        if (!is_file($this->listFile)) {
            $this->Logger->log("Error file does not exist: " . $this->listFile);
            return;
        }

        $resolvedRecords = dns_get_record($_SERVER["HTTP_HOST"], DNS_ANY);

        // Проверяем, совпадает ли исходный IP с одним из разрешенных
        if (!empty($resolvedRecords)) {
            $this->Logger->log("Adding IP addresses of host $_SERVER[HTTP_HOST] to exceptions");
            foreach ($resolvedRecords as $record) {
                if ($record['type'] == 'A' || $record['type'] == 'AAAA') {
                    $this->add($record['type'] == 'AAAA' ? $record['ipv6'] : $record['ip'], $_SERVER["HTTP_HOST"]);
                }
            }
        }
    }

    protected function getDefaultFileContent()
    {
        return "# Белый список IP-адресов\n# Формат: IP # комментарий\n";
    }
}
