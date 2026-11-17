<?php

namespace WAFSystem;

class FingerPrint extends ListBase
{
    public $listName = 'blacklist_fingerprint';
    public $enabled = false;
    public $addBlacklistIP = false;
    public $action = 'BLOCK';

    private $modulName = 'fingerprint_checker';

    public function __construct(Config $config, Logger $logger)
    {
        $this->enabled = $config->init($this->modulName, 'enabled', $this->enabled);
        $this->action = $config->init($this->modulName, 'action', $this->action, 'BLOCK - заблокировать, SKIP - ничего не делать');
        $this->addBlacklistIP = $config->init($this->modulName, 'add_blacklist_ip', $this->addBlacklistIP, 'On - добавить ip в черный список, работает совместно с BLOCK');


        // $this->Config->init('checks', 'fingerprint', true, 'блокировка по FingerPrint');

        $fileName = $config->get($this->modulName, $this->listName);
        if ($fileName === NULL) {
            $fileName = "lists/" . $this->listName;
            $config->set($this->modulName, $this->listName, $fileName, 'список');
        }
        $fileName = ltrim($fileName, "/\\");
        if (empty($fileName)) { // Пустой путь отключает проверку по списку.
            $this->enabled = false;
            return;
        }

        parent::__construct($fileName, $config, $logger);
    }

    public function Checking($fp)
    {
        return $this->isListed($fp);
    }

    protected function createDefaultFileContent()
    {
        $defaultContent = <<<EOT
# {$this->listName}
# Список FingerPrint
# Символ # используется как комментарий.
# 

# Примеры:
# 78e19a6dc46047c556b4a1054e651cbe

EOT;
        return $defaultContent;
    }
}
