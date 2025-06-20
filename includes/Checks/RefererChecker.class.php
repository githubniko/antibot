<?php

namespace WAFSystem;

class RefererChecker extends ListBase
{
    public $enabled = true;
    public $action = 'CAPTCHA';

    private $modulName = 'referer_checker';
    public $listName = 'captcha_referer';

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

        $this->enabled = $config->init($this->modulName, 'enabled', $this->enabled);
        
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
# {$this->action} CПИСОК РЕФЕРЕРОВ
# Можно писать регулярные выражения. Обрабатывается php функцией preg_match(). Правила проверяются поочередно до первого срабатывания.
# Символ # используется как комментарий.
# Примеры:
# ^$ # прямые заходы
# .+ # все рефереры
# ^http://
# или g..le.com
# *.yandex
# *.mail.ru

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
