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

    private function __construct()
    {
        $this->Host = isset($_SERVER['HTTP_HOST']) ?  $_SERVER['HTTP_HOST'] : '';
        $this->Ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
        $this->UserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

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
        return md5(rand(100, 200));
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
