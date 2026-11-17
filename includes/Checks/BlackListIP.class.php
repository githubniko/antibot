<?php
namespace WAFSystem;

class BlackListIP extends ListBase
{
    public $listName = 'blacklist_ip';
    public $enabled = true;
    public $ipv6 = 'CAPTCHA';
    
    private $modulName = 'ip_checker';

    public function __construct(Config $config, Logger $logger)
    {
        $this->enabled = $config->init($this->modulName, 'enabled', $this->enabled);
        $this->ipv6  = $config->init($this->modulName, 'ipv6', $this->ipv6, 'CAPTCHA - капча, BLOCK - заблокировать, SKIP - ничего не делать');

        $fileName = $config->get($this->modulName, $this->listName);
        if ($fileName === NULL) {
            $fileName = "lists/" . $this->listName;
            $config->set($this->modulName, $this->listName, $fileName, 'черный список');
        }
        $fileName = ltrim($fileName, "/\\");
        if (empty($fileName)) { // Пустой путь отключает проверку по списку.
            $this->enabled = false;
            return;
        }

        parent::__construct($fileName, $config, $logger);
    }

    protected function eventInitListFile()
    {
    }

    protected function createDefaultFileContent()
    {
        $defaultContent = <<<EOT
# {$this->listName}
# Черный список IP-адресов
# Формат: 
#  IP # комментарий
#  IP/mask # комментарий
#  IP-IP # комментарий

EOT;
        return $defaultContent;
    }

    protected function validate($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    protected function Comparison($value1, $value2) 
    {
        return \Utility\Network::isInRange($value2, $value1);
    }

    public function isIPv6($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }
}