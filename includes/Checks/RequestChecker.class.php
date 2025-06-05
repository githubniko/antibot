<?php
namespace WAFSystem;

class RequestChecker extends ListBase
{
    public $listName = 'whitelist_url';
    public $enabled = true;
    public $action = 'ALLOW';

    private $modulName = 'request_checker';

    public function __construct(Config $config, Logger $logger)
    {
        $this->enabled = $config->init($this->modulName, 'enabled', $this->enabled, 'исключения для указанных URL');
        $this->action = $config->init($this->modulName, 'action', $this->action, 'ALLOW - разрешить, SKIP - ничего не делать');

        $file = ltrim($config->get($this->modulName, $this->listName, ''), "/\\");
        if (empty($file)) {
            $file = "lists/" . $this->listName;
            $config->set($this->modulName, $this->listName, $file);
        }

        parent::__construct($file, $config, $logger);
    }

    protected function createDefaultFileContent()
    {
        $defaultContent = <<<EOT
# Список исключений по REQUEST_URI
# Можно писать регулярные выражения. Обрабатывается php функцией preg_match().
# Символ # используется как комментарий.

# Примеры:
# ^/api/ - простое совпадение
# ^/admin/ - только если начинается с /admin/
# \.(css|js|png)$ - файлы с указанными расширениями
# /admin/.*\.json$ # Админские JSON-запросы

EOT;
        return $defaultContent;
    }

    protected function Comparison($value1, $value2) 
    {
        $pattern = str_replace('/', '\/', $value1); // Экранируем слеши для регулярки
        return preg_match("/$pattern/iu", $value2) === 1;
    }
}
