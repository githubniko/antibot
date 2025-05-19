<?php

namespace WAFSystem;

class Config
{
    private $Lock;

    private static $instances = [];
    private $config = [];
    private $comments = [];
    public $BasePath;
    public $DOCUMENT_ROOT;
    public $ANTIBOT_PATH;
    public $HTTP_HOST;
    public $HTTPS;
    public $configFileName = 'config.ini';
    private $configFile;
    private $useBooleanAsOnOff = true;

    private function __construct($documentRoot, $antibotPath)
    {
        $this->DOCUMENT_ROOT = $documentRoot;
        $this->ANTIBOT_PATH = $antibotPath;
        $this->HTTP_HOST = getenv("HTTP_HOST");
        if (isset($_SERVER['HTTPS'])) {
            $this->HTTPS =  $_SERVER['HTTPS'] === 'on' ? true : false;
        } else {
            $this->HTTPS = isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https' ? true : null;
        }

        
        $this->BasePath = rtrim($documentRoot, "/\\") . '/' . ltrim($antibotPath, "/\\");
        $this->configFile = $this->BasePath . $this->configFileName;
        $this->Lock = new Lock($this->BasePath . pathinfo($this->configFileName, PATHINFO_FILENAME) .'.lock');
        $this->loadConfig();
    }

    public static function getInstance($documentRoot = null, $antibotPath = null)
    {
        if ($documentRoot === null) {
            $documentRoot = rtrim(getenv("DOCUMENT_ROOT"), "/\\");
        }

        if ($antibotPath === null) {
            $antibotPath = '/antibot/';
        }

        $key = md5($documentRoot . $antibotPath);

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($documentRoot, $antibotPath);
        }

        return self::$instances[$key];
    }

    public function setBooleanFormat($useOnOff)
    {
        $this->useBooleanAsOnOff = (bool)$useOnOff;
    }

    private function sanitizeKey($key)
    {
        $key = preg_replace('/[^a-zA-Z0-9_\-]/', '', $key);

        if (preg_match('/^[0-9]/', $key)) {
            $key = 'key_' . $key;
        }

        if (empty($key)) {
            $key = 'key_' . uniqid();
        }

        return $key;
    }

    private function loadConfig()
    {
        $iniFile = $this->configFile;
        if (!file_exists($iniFile)) {
            $this->config = [];
            $this->comments = [];
            return;
        }

        $lines = file($iniFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $currentSection = '';
        $this->config = [];
        $this->comments = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/^\[([^]]+)\]$/', $line, $matches)) {
                $currentSection = $this->sanitizeKey($matches[1]);
                $this->comments[$currentSection]['__section_comment__'] = '';
                continue;
            }

            if (strpos($line, ';') === 0) {
                if ($currentSection === '') {
                    $this->comments['__global_comments__'][] = $line;
                } else {
                    $this->comments[$currentSection]['__section_comment__'] .= $line . "\n";
                }
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = array_map('trim', explode('=', $line, 2));
                $key = $this->sanitizeKey($key);

                $paramComment = '';
                if (($commentPos = strpos($value, ';')) !== false) {
                    $paramComment = substr($value, $commentPos);
                    $value = trim(substr($value, 0, $commentPos));
                }

                $value = $this->parseValue($value);

                $this->config[$currentSection][$key] = $value;
                if ($paramComment !== '') {
                    $this->comments[$currentSection][$key] = $paramComment;
                }
            }
        }
    }

    private function parseValue($value)
    {
        if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
            return $matches[1];
        }

        $lowerValue = strtolower($value);
        $booleanMap = [
            'true' => true,
            'on' => true,
            'yes' => true,
            '1' => true,
            'false' => false,
            'off' => false,
            'no' => false,
            '0' => false
        ];

        if (isset($booleanMap[$lowerValue])) {
            return $booleanMap[$lowerValue];
        }

        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }

        return $value;
    }

    public function get($section, $key, $default = null)
    {
        $section = $this->sanitizeKey($section);
        $key = $this->sanitizeKey($key);

        if (isset($this->config[$section][$key])) {
            return $this->config[$section][$key];
        }

        return $default;
    }
    /**
     * Изменяет или устанавливает параметр
     */
    public function set($section, $key, $value, $comment = null)
    {
        $section = $this->sanitizeKey($section);
        $key = $this->sanitizeKey($key);

        if (!isset($this->config[$section])) {
            $this->config[$section] = [];
            $this->comments[$section]['__section_comment__'] = '';
        }

        $this->config[$section][$key] = $value;

        if ($comment !== null) {
            $this->comments[$section][$key] = '; ' . ltrim($comment, '; ');
        }

        $this->saveConfig();

        return $value;
    }
    /**
     * Если параметр не существует, то создает его. Возвращает значение созданного или текущего значения
     */
    public function init($section, $key, $value, $comment = null)
    {
        $getValue = $this->get($section, $key);
        if (is_null($getValue)) {
            return $this->set($section, $key, $value, $comment);
        }
        return $getValue;
    }

    private function formatValue($value)
    {
        if (is_bool($value)) {
            return $this->useBooleanAsOnOff
                ? ($value ? 'On' : 'Off')
                : ($value ? 'true' : 'false');
        }

        if (is_array($value)) {
            return '"' . implode(',', array_map('addslashes', $value)) . '"';
        }

        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (preg_match('/^[a-zA-Z0-9_\-\.]+$/', $value) && !is_numeric($value)) {
            return $value;
        }

        return '"' . addslashes($value) . '"';
    }

    private function saveConfig()
    {
        $content = '';

        if (!empty($this->comments['__global_comments__'])) {
            $content .= implode("\n", $this->comments['__global_comments__']) . "\n\n";
        }

        foreach ($this->config as $section => $params) {
            if (!empty($this->comments[$section]['__section_comment__'])) {
                $content .= trim($this->comments[$section]['__section_comment__']) . "\n";
            }

            $content .= "[$section]\n";

            foreach ($params as $key => $value) {
                $line = "$key = " . $this->formatValue($value);

                if (!empty($this->comments[$section][$key])) {
                    $line .= ' ' . $this->comments[$section][$key];
                }

                $content .= $line . "\n";
            }

            $content .= "\n";
        }

        $this->Lock->Lock();
        try {
            $tempFile = tempnam(dirname($this->configFile), 'tmp_');

            if (file_put_contents($tempFile, $content) === false) {
                throw new \RuntimeException("Failed to write temp config file");
            }

            if (!rename($tempFile, $this->configFile)) {
                @unlink($tempFile);
                throw new \RuntimeException("Failed to replace config file");
            }

            chmod($this->configFile, 0644);
        } finally {
            $this->Lock->Unlock();
        }
    }

    public function getAll()
    {
        $this->loadConfig();
        return $this->config;
    }

    public function getAllWithComments()
    {
        $this->loadConfig();
        return [
            'config' => $this->config,
            'comments' => $this->comments
        ];
    }
    public function reloadConfig()
    {
        $this->loadConfig();
    }
}
