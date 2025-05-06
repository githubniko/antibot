<?php
namespace WAFSystem;

class Profile
{
    private static $_instances = null;
    public $RayID;
    public $RayIDSecret;
    public $Host;
    public $Ip;
    public $UserAgent;
    public $isIPv6;
    public $Referer;
    public $REQUEST_URI;

    private function __construct()
    {
        $this->Host = isset($_SERVER['HTTP_HOST']) ?  $_SERVER['HTTP_HOST'] : '';
        $this->Ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $this->UserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 512) : '';
        $this->Referer = isset($_SERVER['HTTP_REFERER']) ? mb_substr($_SERVER['HTTP_REFERER'], 0, 512) : '';
        $this->REQUEST_URI = isset($_SERVER['REQUEST_URI']) ? mb_substr($_SERVER['REQUEST_URI'], 0, 512) : '';
        
        $this->isIPv6 = filter_var($this->Ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

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
        return bin2hex(random_bytes(32));
    }

    # генерирует идентификатор пользователя для идентификации в лог-файле
    private function getRayID()
    {
        return substr(md5($this->Host . $this->Ip . $this->UserAgent), 0, 16);
    }

    # генерирует идентификатор пользователя для маркера
    private function getRayIDSecret()
    {
        return substr(md5($this->Host . $this->Ip . $this->UserAgent), 16);
    }
}
