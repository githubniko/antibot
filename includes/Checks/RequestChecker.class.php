<?php
namespace WAFSystem;


class RequestChecker extends ListBase
{
    public $listName = 'whitelist_url';

    public function __construct(Config $config, Logger $logger)
    {
        $file = ltrim($config->get('lists', $this->listName, ''), "/\\");
        if (empty($file)) {
            $file = "lists/" . $this->listName;
            $config->set('lists', $this->listName, $file);
        }

        parent::__construct($file, $config, $logger);
    }

    /**
     * Проверяет соответствие URI шаблону
     */
    private function matchPattern($pattern, $uri)
    {
        // Экранируем слеши для регулярки
        $pattern = str_replace('/', '\/', $pattern);

        // Простая проверка на наличие спецсимволов regex
        if (!preg_match('/[\.\*\?\+\^\$\{\}\(\)\|\[\]]/', $pattern)) {
            // Если нет спецсимволов - простое сравнение
            return stripos($uri, $pattern) !== false;
        }

        // Полноценная проверка по regex
        return preg_match("/$pattern/i", $uri) === 1;
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
        if ($this->matchPattern($value1, $value1))
           return true;
        return false;
    }
}
