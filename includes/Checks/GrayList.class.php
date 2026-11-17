<?php
namespace WAFSystem;

class GrayList extends ListBase
{
    public $listName = 'graylist';
    private $modulName = 'main';

    public function __construct(Config $config, Logger $logger)
    {
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

    protected function eventInitListFile()
    {
    }

    protected function createDefaultFileContent()
    {
        $defaultContent = <<<EOT
# {$this->listName}
# Серый список, хранит информацию требующую ручной проверки
# Формат: данные # тип данных

EOT;
        return $defaultContent;
    }
}