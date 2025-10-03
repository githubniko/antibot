<?php
namespace WAFSystem;

class UserAgentChecker extends ListBase
{
    protected $listName = 'whitelist_useragent';

    public $action = 'ALLOW';
    public $enabled = true;
    
    private $modulName = 'useragent_checker';
    private $minLength = 5;
    private $maxLength = 512;
    

    public function __construct(Config $config, Logger $logger, $params = [])
    {
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
        $this->action = $config->init($this->modulName, 'action', $this->action, 'ALLOW - разрешить, SKIP - ничего не делать');
        $this->minLength = $config->init($this->modulName, 'min_length', $this->minLength, 'минимальная длина user-agent');
        $this->maxLength = $config->init($this->modulName, 'max_length', $this->maxLength, 'максимальная длина user-agent');

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

    protected function createDefaultFileContent()
    {
        $defaultContent = <<<EOT
# Список совпадений User-agent
# Можно писать регулярные выражения. Обрабатывается php функцией preg_match(). Правила проверяются поочередно до первого срабатывания.
# Символ # используется как комментарий.
# Примеры:
# Myboot 
# или Myb..t
# WhatsApp/[0-9.]+
# WhatsAppBot/[0-9.]+

EOT;
        return $defaultContent;
    }

    protected function Comparison($value1, $value2)
    {
        $pattern = str_replace('/', '\/', $value1); // Экранируем слеши для регулярки
        return preg_match("/$pattern/iu", $value2) === 1;
    }

    /**
     * Проверяет валидность User-Agent
     */
    public function isValid($userAgent)
    {
        $this->Logger->log("UA:  " . $userAgent);
        if (empty($userAgent)) {
            $this->Logger->log("Empty User-Agent string");
            return false;
        }

        // Проверка минимальной/максимальной длины
        if (strlen($userAgent) < $this->minLength || strlen($userAgent) > $this->maxLength) {
            $this->Logger->log("Invalid User-Agent length: " . strlen($userAgent));
            return false;
        }

        return true;
    }

    public function Checking($rerefer)
    {
        if (empty($this->listName)) // возвращаем false, если лист выключен
            return false;

        return $this->isListed($rerefer);
    }
}
