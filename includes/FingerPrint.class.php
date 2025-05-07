<?php

namespace WAFSystem;

include_once 'ListBase.class.php';

class FingerPrint extends ListBase
{
    public $listName = 'blacklist_fingerprint';

    public function __construct(Config $config, Logger $logger)
    {
        $this->Config = $config;

        $this->Config->init('checks', 'fingerprint', true, 'блокировка по FingerPrint');

        $file = ltrim($config->get('lists', $this->listName, ''), "/\\");
        if (empty($file)) {
            $file = "lists/" . $this->listName;
            $config->set('lists', $this->listName, $file);
        }

        parent::__construct($file, $config, $logger);
    }

    # Функция проверяет ip на индексирующего бота
    public function isFP($fp)
    {
        if ($this->Config->get('checks', 'fingerprint', false)) {
            $this->Logger->log("FP: " . $fp);
            return $this->isListed($fp);
        }
    }

    protected function createDefaultFileContent()
    {
        $defaultContent = <<<EOT
# Список исключений по FINGERPRINT
# При совпадении, ip будет добавлять с blacklist
# Символ # используется как комментарий.
# 

# Примеры:
# 78e19a6dc46047c556b4a1054e651cbe

EOT;
        return $defaultContent;
    }
}
