<?php
namespace WAFSystem;

class WhiteListIP extends ListBase
{
    public $listName = 'whitelist_ip';
    public $enabled = true;
    
    private $modulName = 'ip_checker';

    public function __construct(Config $config, Logger $logger)
    {
        $this->enabled = $config->init($this->modulName, 'enabled', $this->enabled);

        $fileName = $config->get($this->modulName, $this->listName);
        if ($fileName === NULL) {
            $fileName = "lists/" . $this->listName;
            $config->set($this->modulName, $this->listName, $fileName, 'белый список');
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
        $resolvedRecords = dns_get_record($_SERVER["HTTP_HOST"], DNS_A + DNS_AAAA);

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
# {$this->listName}
# Белый список IP-адресов
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
}
