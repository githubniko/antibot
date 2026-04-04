<?php

namespace WAFSystem;

use Exception;

class Marker
{
    const COOKIE_PREF = 'aw_';

    private $Config;
    private $profile;
    private $logger;
    private $expireDays;
    private $nameMarker; // команда установки куки
    private $storageType = 'cookie'; // тип хранилища для метки

    public function __construct(Config $config, Profile $profile, Logger $logger)
    {
        $this->Config = $config;
        $this->profile = $profile;
        $this->logger = $logger;

        $config->init('cookie', 'cookie_name', substr($this->profile->genKey(), 0, rand(5, 11)), 'Изменение значения, позволяет сбросить метку всем пользователям');
        $config->init('cookie', 'expire_days', 30, 'дней, действия метки');
        $this->storageType = $config->init('cookie', 'storage_type', $this->storageType, 'тип хранилища для метки (cookie, awsession)');

        $this->expireDays = (int)$config->get('cookie', 'expire_days', 30);
        $this->genNameMarker();
    }

    public function getNameMarker()
    {
        return $this->nameMarker;
    }

    // Устанавливаем имя маркера для каждой сессии
    private function genNameMarker()
    {
        if (isset($_SESSION['name_marker']) && !empty($_SESSION['name_marker']))
            $this->nameMarker = $_SESSION['name_marker'];
        else {
            $this->nameMarker = \Utility\GenerateRandomName::genFuncName();
            $_SESSION['name_marker'] = $this->nameMarker;
        }
    }

    function set($time = null)
    {
        if ($time == null)
            $time = time() + $this->expireDays * 86400;

        $cookie_value = $this->profile->RayID;
        $cookie_key = self::COOKIE_PREF . $this->profile->RayIDSecret;

        if ($this->storageType == "awsession") {
            $Session = \WAFSystem\WAFSystem::getInstance()->Session;
            $Session->set($cookie_key, $cookie_value, $time);

        } else {
            if (version_compare(PHP_VERSION, '7.3.0') >= 0) {
                setcookie($cookie_key, $cookie_value, [
                    'expires' => $time,
                    'path' => '/',
                    'httponly' => true,
                    'secure' => isset($_SERVER['HTTPS'])
                ]);
            } else {
                setcookie($cookie_key, $cookie_value, time() + $this->expireDays * 24 * 3600, "/");
            }
        }

        $this->logger->logMessage("Tag set");
        $this->genNameMarker();
    }

    function remove()
    {
        $this->set(time() - 3600);
    }

    function isValid()
    {
        $cookie_key = self::COOKIE_PREF . $this->profile->RayIDSecret;
        if ($this->storageType == "awsession") {
            $Session = \WAFSystem\WAFSystem::getInstance()->Session;
            if(!is_null($Session->get($cookie_key))) {
                return true;
            }
                
        } else {
            if (isset($_COOKIE[$cookie_key])) {
                return true;
            }
        }

        return false;
    }
}
