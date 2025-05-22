<?php
namespace WAFSystem;

class WhiteListUserAgent extends ListBase
{
    public $listName = 'whitelist_useragent';

    public function __construct(Config $config, Logger $logger)
    {
        $config->init('checks', 'useragent', true, 'блокировка, если User-Agent отсутствуте или пустой');
        $config->init('useragent', 'min_length', 20);
        $config->init('useragent', 'max_length', 512);

        $file = ltrim($config->get('lists', $this->listName, ''), "/\\");
        if (empty($file)) {
            $file = "lists/" . $this->listName;
            $config->set('lists', $this->listName, $file);
        }
        parent::__construct($file, $config, $logger);
    }

    protected function createDefaultFileContent()
    {
        $defaultContent = <<<EOT
# Список разрешающих совпадений User-agent
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

    private function matchPattern($pattern, $value)
    {
        // Экранируем слеши для регулярки
        $pattern = str_replace('/', '\/', $pattern);

        // Простая проверка на наличие спецсимволов regex
        if (!preg_match('/[\.\*\?\+\^\$\{\}\(\)\|\[\]]/', $pattern)) {
            // Если нет спецсимволов - простое сравнение
            return stripos($value, $pattern) !== false;
        }

        // Полноценная проверка по regex
        return preg_match("/$pattern/i", $value) === 1;
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
        $minLength = $this->Config->get('useragent', 'min_length', 20);
        $maxLength = $this->Config->get('useragent', 'max_length', 512);

        if (strlen($userAgent) < $minLength || strlen($userAgent) > $maxLength) {
            $this->Logger->log("Invalid User-Agent length: " . strlen($userAgent));
            return false;
        }

        return true;
    }
}
