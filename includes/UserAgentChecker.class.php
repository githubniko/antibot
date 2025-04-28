<?php
namespace WAFSystem;

class UserAgentChecker {
    private $config;
    private $logger;
    private $rulesFile;
    private $allowedPatterns = [];
    private $excludedPatterns = [];

    public function __construct(Config $config, Logger $logger) {
        $this->config = $config;
        $this->logger = $logger;
        
        $file = ltrim($config->get('lists', 'useragent_rules'), "/\\");
        if ($file == null) {
            $file = "lists/useragent";
        }
        $this->rulesFile = $config->BasePath . $file;
        
        $this->loadRules();
    }

    /**
     * Проверяет валидность User-Agent
     */
    public function isValid($userAgent) {
        if (empty($userAgent)) {
            $this->logger->log("Empty User-Agent string");
            return false;
        }

        // Проверка минимальной/максимальной длины
        $minLength = $this->config->get('useragent', 'min_length', 20);
        $maxLength = $this->config->get('useragent', 'max_length', 512);
        
        if (strlen($userAgent) < $minLength || strlen($userAgent) > $maxLength) {
            $this->logger->log("Invalid User-Agent length: " . strlen($userAgent));
            return false;
        }

        return true;
    }

    /**
     * Проверяет, является ли User-Agent исключением (разрешенные боты и сервисы)
     */
    public function isExcludedBot($userAgent) {
        if (empty($userAgent)) {
            return false;
        }

        foreach ($this->excludedPatterns as $pattern) {
            if (preg_match("/$pattern/iu", $userAgent) == 1) {
                $this->logger->log("User-Agent matched excluded pattern: " . $pattern);
                return true;
            }
        }

        return false;
    }

    /**
     * Проверяет соответствие разрешенным шаблонам
     */
    public function isAllowed($userAgent) {
        if (empty($userAgent)) {
            return false;
        }

        # Если шаблоны не указаны, то пропускаем
        if(empty($this->allowedPatterns)) {
            return false;
        }

        # Если шаблоны указаны, то разершаем только их
        foreach ($this->allowedPatterns as $pattern) {
            if (preg_match("/$pattern/iu", $userAgent)) {
                $this->logger->log("User-Agent matched allowed pattern: " . $pattern);
                return true;
            }
        }

        $this->logger->log("User-Agent not found in template list");
        return false;
    }

    /**
     * Загружает правила из файла
     */
    private function loadRules() {
        if (!file_exists($this->rulesFile)) {
            $this->createDefaultRulesFile();
            return;
        }

        $file = fopen($this->rulesFile, 'r');
        if (!$file) {
            $this->logger->log("Failed to open User-Agent rules file");
            return;
        }

        $section = null;
        while (($line = fgets($file)) !== false) {
            $line = trim($line);
            
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // Определение секций
            if (preg_match('/^\[(.*?)\]$/', $line, $matches)) {
                $section = strtolower($matches[1]);
                continue;
            }

            // Добавление шаблонов в соответствующие секции
            $pattern = $this->preparePattern($line);
            if ($section === 'allowed') {
                $this->allowedPatterns[] = $pattern;
            } elseif ($section === 'excluded') {
                $this->excludedPatterns[] = $pattern;
            }
        }

        fclose($file);
    }

    /**
     * Подготавливает regex-шаблон из строки правила
     */
    private function preparePattern($line) {
        // Удаление комментариев
        $pattern = trim(preg_replace('/#.*$/', '', $line));
        
        // Если шаблон не regex - делаем его точным соответствием
        // if (!preg_match('/^\/.+\/[a-z]*$/', $pattern)) {
        //     $pattern = '/' . preg_quote($pattern, '/') . '/i';
        // }

        return $pattern;
    }

    /**
     * Создает файл правил по умолчанию
     */
    private function createDefaultRulesFile() {
        $defaultContent = <<<EOT
# Правила для User-Agent
# Формат: [section]
#   pattern # комментарий
# Можно указывать регулярные выражения

[allowed]
# Только разрешенные User-Agent. Если указано, то все остальное будет блокироваться
#^Mozilla.*
#^Chrome.*
#^Safari.*
#^Edge.*

[excluded]
# Исключения (разрешенные боты). Всегда будут пропускаться. Имеет приоритет над [allowed]
#Googlebot
#Bingbot
#YandexBot
#FacebookBot
#Twitterbot
#WhatsApp
#Slackbot
EOT;

        if (!file_put_contents($this->rulesFile, $defaultContent)) {
            $this->logger->log("Failed to create default User-Agent rules file");
        }
    }
}
