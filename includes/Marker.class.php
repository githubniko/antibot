<?php

namespace WAFSystem;

class Marker
{
    private $Config;
    private $profile;
    private $logger;
    private $expireDays;

    public function __construct(Config $config, Profile $profile, Logger $logger)
    {
        $this->Config = $config;
        $this->profile = $profile;
        $this->logger = $logger;
        $this->expireDays = (int)$config->get('cookie', 'expire_days', 30);
    }

    function set($time = null)
    {
        if ($time == null)
            $time = time() + $this->expireDays * 86400;

        $cookie_value = $this->Config->get('main', 'cookie_name', '');
        if (version_compare(PHP_VERSION, '7.3.0') >= 0) {
            setcookie($this->profile->RayIDSecret, $cookie_value, [
                'expires' => $time,
                'path' => '/',
                'httponly' => true,
                'secure' => isset($_SERVER['HTTPS'])
            ]);
        } else {
            setcookie($this->profile->RayIDSecret, $cookie_value, time() + $this->expireDays * 24 * 3600, "/");
        }
        $this->logger->logMessage("Tag set");
    }

    function remove()
    {
        $this->set(time() - 3600);
    }

    function isValid()
    {
        if (
            isset($_COOKIE[$this->profile->RayIDSecret])
            && $_COOKIE[$this->profile->RayIDSecret] == $this->Config->get('main', 'cookie_name', '')
        ) {
            return true;
        }
        return false;
    }
}
