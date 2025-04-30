<?php
namespace WAFSystem;

class Config
{
    private static $instances = [];
    private $config = [];
    public $BasePath;
    public $DOCUMENT_ROOT;
    public $ANTIBOT_PATH;
    public $HTTP_HOST;

    private function __construct($documentRoot, $antibotPath)
    {
        $this->DOCUMENT_ROOT = $documentRoot;
        $this->ANTIBOT_PATH = $antibotPath;
        $this->HTTP_HOST = getenv("HTTP_HOST");

        $this->BasePath = rtrim($documentRoot, "/\\") . '/' . ltrim($antibotPath, "/\\");
        $this->loadConfig();
    }

    public static function getInstance($documentRoot = null, $antibotPath = null)
    {
        if ($documentRoot === null) {
            $documentRoot = rtrim(getenv("DOCUMENT_ROOT"), "/\\");
        }

        if ($antibotPath === null) {
            $antibotPath = '/antibot/'; // значение по умолчанию
        }

        $key = md5($documentRoot . $antibotPath);

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($documentRoot, $antibotPath);
        }

        return self::$instances[$key];
    }

    private function loadConfig()
    {
        $iniFile = $this->BasePath . 'config.ini';
        if (!file_exists($iniFile)) {
            throw new \RuntimeException("Config file not found: $iniFile");
        }

        $this->config = parse_ini_file($iniFile, true);
        if ($this->config === false) {
            throw new \RuntimeException("Failed to parse config file");
        }

        $this->validateConfig();
    }

    private function validateConfig()
    {
        $required = [
            'main' => ['debug', 'header404', 'cookie_name'],
            'cookie' => ['expire_days'],
            'paths' => ['antibot_path']
        ];

        foreach ($required as $section => $keys) {
            foreach ($keys as $key) {
                if (!isset($this->config[$section][$key])) {
                    throw new \RuntimeException("Missing required config parameter: $section.$key");
                }
            }
        }
    }

    public function get($section, $key, $default = null)
    {
        return isset($this->config[$section][$key]) ? $this->config[$section][$key] : $default;
    }

    public function getAll()
    {
        return $this->config;
    }
}
