<?php
namespace WAFSystem;

class Profile
{
    private static $_instances = null;
    public $RayID;
    public $RayIDSecret;
    public $Host;
    public $IP;
    public $Protocol;
    public $UserAgent;
    public $isIPv6;
    public $Referer;
    public $REQUEST_URI;
    public $FingerPrint;

    private function __construct()
    {
        $this->Host = isset($_SERVER['HTTP_HOST']) ?  $_SERVER['HTTP_HOST'] : '';
        $this->IP = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $this->Protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : '';
        $this->UserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 512) : '';
        $this->Referer = isset($_SERVER['HTTP_REFERER']) ? mb_substr($_SERVER['HTTP_REFERER'], 0, 512) : '';
        $this->REQUEST_URI = isset($_SERVER['REQUEST_URI']) ? mb_substr($_SERVER['REQUEST_URI'], 0, 512) : '';
        
        $this->isIPv6 = filter_var($this->IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

        $this->RayID = $this->getRayID();
        $this->RayIDSecret = $this->getRayIDSecret();
    }

    public static function getInstance()
    {

        if (is_null(self::$_instances)) {
            self::$_instances = new self();
        }
        return self::$_instances;
    }

    # генерирует случайный код
    public function genKey()
    {
        if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
            return bin2hex(random_bytes(32));
        } else {
            return bin2hex($this->random_bytes_php5(32));
        }
        
    }

    # альтернатива random_bytes() для PHP < 7.0.0
    public function random_bytes_php5($length) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if ($strong === true) {
                return $bytes;
            }
        }
        // Если openssl недоступен, можно использовать менее безопасные варианты
        throw new \RuntimeException('Не удалось сгенерировать криптографически безопасные данные');
    }

    # генерирует идентификатор пользователя для идентификации в лог-файле
    private function getRayID()
    {
        return substr(md5($this->Host . $this->IP . $this->UserAgent), 0, 16);
    }

    # генерирует идентификатор пользователя для маркера
    private function getRayIDSecret()
    {
        return substr(md5($this->Host . $this->IP . $this->UserAgent), 16);
    }
}
