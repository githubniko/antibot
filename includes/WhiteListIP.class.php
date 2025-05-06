<?php

namespace WAFSystem;

include_once 'ListBase.class.php';

class WhiteListIP extends ListBase
{
    public function __construct(Config $config, Logger $logger)
    {

        $file = ltrim($config->get('lists', 'whitelist_ip'), "/\\");
        if ($file == null) {
            $file = "lists/whitelist_ip";
        }

        parent::__construct($file, $config, $logger);
    }

    protected function eventInitListFile()
    {
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

    protected function createDefaultFileContent()
    {
        $defaultContent = <<<EOT
# Белый список IP-адресов
# Формат: IP # комментарий

EOT;
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
}
