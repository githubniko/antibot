<?php
namespace WAFSystem;

include_once 'ListBase.class.php';

class BlackListIP extends ListBase
{
    public function __construct(Config $config, Logger $logger)
    {

        $file = ltrim($config->get('lists', 'blacklist_ip'), "/\\");
        if ($file == null) {
            $file = "lists/blacklist_ip";
        }

        parent::__construct($file, $config, $logger);
    }

    protected function eventInitListFile()
    {
    }

    protected function createDefaultFileContent()
    {
        $defaultContent = <<<EOT
# Черный список IP-адресов
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