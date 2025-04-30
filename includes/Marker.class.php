<?php

namespace WAFSystem;

class Marker
{
    private $profile;
    private $logger;
    private $expireDays;

    public function __construct(Config $config, Profile $profile, Logger $logger)
    {
        $this->profile = $profile;
        $this->logger = $logger;
        $this->expireDays = (int)$config->get('cookie', 'expire_days', 30);
    }

    function set($time = null)
    {
        if($time == null) 
            $time = time() + $this->expireDays * 86400;
        
        if (version_compare(PHP_VERSION, '7.3.0') >= 0) {
            setcookie($this->profile->RayIDSecret, $this->profile->genKey(), [
                'expires' => $time,
                'path' => '/',
                'httponly' => true,
                'secure' => isset($_SERVER['HTTPS'])
            ]);
        } else {
            setcookie($this->profile->RayIDSecret, $this->profile->genKey(), time() + $this->expireDays * 24 * 3600, "/");
        }
        $this->logger->logMessage("Tag set");
    }

    function remove()
    {
        $this->set(time() - 3600);
    }

    function isValid()
    {
        if (isset($_COOKIE[$this->profile->RayIDSecret])) {
            return true;
        }
        return false;
    }
}
