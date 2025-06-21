<?php
namespace WAFSystem;

class RequestChecker extends ListBase
{
    public $enabled = true;
    public $action = 'ALLOW';

    private $modulName = 'request_checker';
    public $listName = 'whitelist_uri';

    public function __construct(Config $config, Logger $logger, $params = [])
    {
        $this->Logger = $logger;

        if (sizeof($params) > 0) {
            foreach ($params as $key => $value) {
                if (isset($this->{$key})) {
                    if (empty($value))
                        throw new \Exception($key . ' cannot be empty.');
                    $this->{$key} = $value;
                }
            }
        }
        $this->enabled = $config->init($this->modulName, 'enabled', $this->enabled, 'исключения для указанных URL');

        $listName = $config->get($this->modulName, $this->listName);
        $file = ltrim($listName, "/\\");
        if ($listName === NULL) {
            $file = "lists/" . $this->listName;
            $config->set($this->modulName, $this->listName, $file, 'список');
        }
        
        if(empty($listName)) {
            $this->enabled = false;
            return;
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
favicon.ico

EOT;
        return $defaultContent;
    }

    protected function Comparison($value1, $value2) 
    {
        $pattern = str_replace('/', '\/', $value1); // Экранируем слеши для регулярки
        mb_regex_encoding('UTF-8');
        return preg_match("/$pattern/iu", $value2) === 1;
    }
}
