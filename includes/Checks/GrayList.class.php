<?php
namespace WAFSystem;

class GrayList extends ListBase
{
    public $listName = 'graylist';
    private $modulName = 'main';

    public function __construct(Config $config, Logger $logger)
    {
        $file = ltrim($config->get($this->modulName, $this->listName, ''), "/\\");
        if (empty($file)) {
            $file = "lists/" . $this->listName;
            $config->set($this->modulName, $this->listName, $file);
        }

        parent::__construct($file, $config, $logger);
    }

    protected function eventInitListFile()
    {
    }

    protected function createDefaultFileContent()
    {
        $defaultContent = <<<EOT
# Серый список, хранит информацию требующую ручной проверки
# Формат: данные # тип данных

EOT;
        return $defaultContent;
    }
}