<?php

namespace WAFSystem;

// ... Реализация работы с капчей
class Api
{
    private static $_instances = null;
    private $WAFSystem;
    private $data; // хранит массив данных из php://input

    public function __construct(WAFSystem $wafsystem)
    {
        $this->WAFSystem = $wafsystem;
        $this->data = json_decode(file_get_contents('php://input'), true);
        $client_ip = $this->WAFSystem->Profile->IP;

        if (empty($this->data)) {
            $message = "Error: Data is empty";
            $this->WAFSystem->Logger->log($message, [static::class]);
            $this->BlockIP($client_ip, $message);
            $this->endJSON('block');
        }

        if (!isset($this->data['func'])) {
            $message = "Error: Value 'func' is not set";
            $this->WAFSystem->Logger->log($message, [static::class]);
            $this->BlockIP($client_ip, $message);
            $this->endJSON('block');
        }

        if ($this->data['func'] == 'csrf_token') {
            $this->WAFSystem->Logger->rotateIfNeeded(); // делаем ротация логов, пока пользователь ожидает проверку
            echo json_encode([
                'func' => $this->data['func'],
                'csrf_token' => $this->createCSRF()
            ]);
            exit;
        }

        if (!$this->isCSRF()) {
            $message = "Error: Value csrf_token is not set";
            $this->WAFSystem->Logger->log($message, [static::class]);
            $this->BlockIP($client_ip, $message);
            $this->endJSON('block');
        }

        if (!$this->isCSRFRequest()) {
            $message = "Error: Value csrf_token is not set";
            $this->WAFSystem->Logger->log($message, [static::class]);
            $this->BlockIP($client_ip, $message);
            $this->endJSON('block');
        }

        if (!$this->validCSRF()) {
            $message = "Error: Invalid csrf_token";
            $this->WAFSystem->Logger->log($message, [static::class]);
            $this->BlockIP($client_ip, $message);
            $this->endJSON('block');
        }
    }

    public static function getInstance(WAFSystem $wafsystem)
    {

        if (is_null(self::$_instances)) {
            self::$_instances = new self($wafsystem);
        }
        return self::$_instances;
    }

    public function endJSON($status)
    {
        $res = array('status' => $status);
        if (!session_id()) {
            $res = "Critical error: Session session_start() not started.";
            $this->WAFSystem->Logger->log($res, [static::class]);
            echo json_encode($res);
            exit;
        }

        if ($status == 'captcha') {
            $res['csrf_token'] = $this->createCSRF(); // # каждый раз генерируем ключ, чтобы форму не DDOS 
            $this->WAFSystem->Logger->log("Show captcha");
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
        return $_SESSION['csrf_token'] = $this->WAFSystem->Profile->genKey();
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

    /**
     * Блокирует айпи, если его нет в белых списках и других правилах исключения
     */
    private function BlockIP($client_ip, $message)
    {
        if (!$this->WAFSystem->WhiteListIP->isListed($client_ip) && !$this->WAFSystem->IndexBot->isIndexbot($client_ip)) {
            $this->WAFSystem->BlackLiskIP->add($this->WAFSystem->Profile->IP, $message);
        }
    }
}
