<?php

namespace WAFSystem;

class Profile
{
    private $Config;

    private static $_instances = null;
    private $salt = '';

    public $RayID;
    public $RayIDSecret;
    public $Host;
    public $IP;
    public $HttpVersion;
    public $UserAgent;
    public $isIPv6;
    public $Referer;
    public $REQUEST_URI;
    public $FingerPrint;
    public $isMobile;

    private function __construct(Config $config)
    {
        $this->Config = $config;
        $HeadProxy = new HeaderProxy($config);

        $this->Host = isset($_SERVER['HTTP_HOST']) ?  $_SERVER['HTTP_HOST'] : '';
        $this->IP = $HeadProxy->getIPAddr();
        $this->HttpVersion = $HeadProxy->getHttpVersion();
        $this->UserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 512) : '';
        $this->Referer = isset($_SERVER['HTTP_REFERER']) ? mb_substr($_SERVER['HTTP_REFERER'], 0, 512) : '';
        $this->REQUEST_URI = isset($_SERVER['REQUEST_URI']) ? mb_substr($_SERVER['REQUEST_URI'], 0, 512) : '';

        $this->isIPv6 = filter_var($this->IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
        $this->isMobile = isset($_SERVER['HTTP_SEC_CH_UA_MOBILE']) ? ($_SERVER['HTTP_SEC_CH_UA_MOBILE'] === '?1' ? true : false) : null; // если null, то далее определяем по косвенным признакам

        $this->salt = $this->Config->get('cookie', 'cookie_name', '');
        $this->RayID = $this->getRayID();
        $this->RayIDSecret = $this->getRayIDSecret();
    }

    public static function getInstance(Config $config)
    {

        if (is_null(self::$_instances)) {
            self::$_instances = new self($config);
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
    public function random_bytes_php5($length)
    {
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
        return substr(md5($this->salt . $this->IP . $this->UserAgent), 0, 16);
    }

    # генерирует идентификатор пользователя для маркера
    private function getRayIDSecret()
    {
        return substr(md5($this->salt . $this->IP . $this->UserAgent), 16);
    }
}
