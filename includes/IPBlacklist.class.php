<?php
namespace WAFSystem;

include_once 'IPList.class.php';

class IPBlacklist extends IPList
{
    public function __construct(Config $config, Logger $logger)
    {

        $file = ltrim($config->get('lists', 'blacklist_ip'), "/\\");
        if ($file == null) {
            $logfile = "lists/blacklist_ip";
        }
        $fullPathFile = $config->BasePath . $file;

        parent::__construct($fullPathFile, $logger);
    }

    protected function eventInitListFile()
    {
    }

    protected function getDefaultFileContent()
    {
        return "# Черный список IP-адресов\n# Формат: IP # комментарий\n";
    }
}