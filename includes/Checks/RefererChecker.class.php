<?php

namespace WAFSystem;

class RefererChecker extends ListBase
{
    public $enabled = true;
    public $action = 'CAPTCHA';
    public $direct = 'CAPTCHA';
    public $referer = 'SKIP';

    protected $HTTP_HOST = '';
    protected $modulName = 'referer_checker';
    protected $listName = 'captcha_referer';

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
        $this->direct = $config->init($this->modulName, 'direct', $this->direct, 'ALLOW - разрешить прямые заходы, CAPTCHA - капча, SKIP - пропустить правило');
        $this->referer = $config->init($this->modulName, 'referer', $this->referer, 'ALLOW - разрешить при наличии реферера, CAPTCHA - капча, SKIP - пропустить правило');
        $this->HTTP_HOST = $config->HTTP_HOST;

        $listName = $config->get($this->modulName, $this->listName);
        $file = ltrim($listName, "/\\");
        if ($listName === NULL) {
            $file = "lists/" . $this->listName;
            $config->set($this->modulName, $this->listName, $file, 'список');
        }

        if (!empty($listName)) { // если лист включен, то загружаем или созаем его 
            parent::__construct($file, $config, $logger);
        }
    }

    /**
     * Прямой заход
     */
    public function isDirect($referer)
    {
        return $this->direct == $this->action && empty($referer); // если реф пуст или содержит локальный домен
    }

    /**
     * Переход с сайта
     */
    public function isReferer($referer)
    {
        return
            $this->referer == $this->action
            && (!empty($referer) && !mb_eregi("^http(s*):\/\/" . $this->HTTP_HOST, $referer)); // если реф не пуст и содержит чужой домен
    }

    public function Checking($rerefer)
    {
        if (empty($this->listName)) // возвращаем false, если лист выключен
            return false;

        return $this->isListed($rerefer);
    }

    protected function createDefaultFileContent()
    {
        $defaultContent = <<<EOT
# {$this->listName}
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
