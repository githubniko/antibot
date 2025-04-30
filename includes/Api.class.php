<?php

namespace WAFSystem;

// ... Реализация работы с капчей
class Api
{
    private static $_instances = null;
    private $Config;
    private $Profile;
    private $Logger;
    private $IpBlacklist;
    private $data; // хранит массив данных из php://input

    public function __construct(Config $config, Profile $profile, Logger $logger, IPBlacklist $ipBlacklist = null)
    {
        $this->Config = $config;
        $this->Profile = $profile;
        $this->Logger = $logger;
        if ($ipBlacklist != null) {
            $this->IpBlacklist = $ipBlacklist;
        }
        $this->data = json_decode(file_get_contents('php://input'), true);

        if (empty($this->data)) {
            $message = "Data is empty";
            $this->Logger->log($message);
            if ($this->IpBlacklist != null)
                $this->IpBlacklist->add($this->Profile->Ip, $message);
            $this->endJSON('block');
        }

        if (!isset($this->data['func'])) {
            $message = "Value 'func' is not set";
            $this->Logger->log($message);
            $this->IpBlacklist->add($this->Profile->Ip, $message);
            $this->endJSON('block');
        }

        if ($this->data['func'] == 'csrf_token') {
            echo json_encode([
                'func' => $this->data['func'],
                'csrf_token' => $this->createCSRF()
            ]);
            exit;
        }

        if (!$this->isCSRF()) {
            $message = "Value csrf_token is not set";
            $this->Logger->log($message);
            if ($this->IpBlacklist != null)
                $this->IpBlacklist->add($this->Profile->Ip, $message);
            $this->endJSON('block');
        }

        if (!$this->isCSRFRequest()) {
            $message = "Value csrf_token is not set";
            $this->Logger->log($message);
            if ($this->IpBlacklist != null)
                $this->IpBlacklist->add($this->Profile->Ip, $message);
            $this->endJSON('block');
        }

        if (!$this->validCSRF()) {
            $message = "Invalid csrf_token";
            $this->Logger->log($message);
            if ($this->IpBlacklist != null)
                $this->IpBlacklist->add($this->Profile->Ip, $message);
            $this->endJSON('block');
        }
    }

    public static function getInstance(Config $config, Profile $profile, Logger $logger, IPBlacklist $ipBlacklist)
    {

        if (is_null(self::$_instances)) {
            self::$_instances = new self($config, $profile, $logger, $ipBlacklist);
        }
        return self::$_instances;
    }

    public function endJSON($status)
    {
        $res = array('status' => $status);
        if (!session_id()) {
            $res = "Critical error: Session session_start() not started.";
            $this->Logger->log($res);
            echo json_encode($res);
            exit;
        }

        if ($status == 'captcha') {
            $res['csrf_token'] = $this->createCSRF(); // # каждый раз генерируем ключ, чтобы форму не DDOS 
            $this->Logger->log("Show captcha");
        }

        $res = array_merge([
            'func' => $this->data['func']
        ], $res);

        echo json_encode($res);
        exit;
    }

    public function getCSRF()
    {
        if (!$this->isCSRF())
            $this->createCSRF();
        return $_SESSION['csrf_token'];
    }

    public function createCSRF()
    {
        return $_SESSION['csrf_token'] = $this->Profile->genKey();
    }
    public function isCSRF()
    {
        return !empty($_SESSION['csrf_token']);
    }

    public function isCSRFRequest()
    {
        return isset($this->data['csrf_token']) && !empty($this->data['csrf_token']);
    }

    public function validCSRF()
    {
        if (!$this->isCSRF())
            return false;

        if ($this->getCSRF() != $this->data['csrf_token'])
            return false;

        return true;
    }

    /**
     * Удаляет CSRF-токен
     * Возвращает true в случае успеха
     */
    public function removeCSRF()
    {
        if ($this->isCSRF())
            unset($_SESSION['csrf_token']);
        return !$this->isCSRF();
    }

    /**
     * Проверяем метод отправки запроса
     */
    public function isPOST()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return true;
        }
        return false;
    }

    /**
     * Получает JSON данные из запроса
     */
    public function getData()
    {
        return $this->data;
    }
}
