<?php

namespace WAFSystem;

class RequestChecker
{
    private $rulesFile;
    private $logger;

    public function __construct(Config $config, Logger $logger)
    {
        $file = ltrim($config->get('lists', 'whitelist_url'), "/\\");
        if ($file == null) {
            $logfile = "lists/whitelist_url";
        }
        $this->rulesFile = $config->BasePath . $file;

        $this->logger = $logger;
        $this->validateRulesFile();
    }

    /**
     * Проверяет, есть ли текущий URL в белом списке
     * @param string $requestUri Проверяемый URI
     * @return bool
     */
    public function isWhitelistedUrl($requestUri)
    {
        if (!file_exists($this->rulesFile)) {
            return false;
        }

        $file = fopen($this->rulesFile, 'r');
        if (!$file) {
            $this->logger->logMessage("Failed to open rules file: " . $this->rulesFile);
            return false;
        }

        try {
            while (($line = fgets($file)) !== false) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }

                // Извлечение шаблона (игнорируем комментарии после #)
                $pattern = trim(preg_replace('/#.*$/', '', $line));
                if (empty($pattern)) {
                    continue;
                }

                // Проверка совпадения с регулярным выражением
                if ($this->matchPattern($pattern, $requestUri)) {
                    fclose($file);
                    $this->logger->logMessage("URL в белом списке: " . $pattern);
                    return true;
                }
            }
        } finally {
            fclose($file);
        }

        return false;
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

    /**
     * Проверяет доступность файла правил
     */
    private function validateRulesFile()
    {
        if (!file_exists($this->rulesFile)) {
            $this->logger->logMessage("Rules file not found, creating: " . $this->rulesFile);
            $this->createDefaultRulesFile();
        } elseif (!is_readable($this->rulesFile)) {
            $this->logger->logMessage("Rules file not readable: " . $this->rulesFile);
            throw new \RuntimeException("Rules file not readable");
        }
    }

    /**
     * Создает файл правил по умолчанию
     */
    private function createDefaultRulesFile()
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

        if (!file_put_contents($this->rulesFile, $defaultContent)) {
            throw new \RuntimeException("Failed to create default rules file");
        }
    }
}
