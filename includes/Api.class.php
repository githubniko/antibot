<?php

namespace WAFSystem;

use Exception;

// ... Реализация работы с капчей
class Api
{
    private static $_instances = null;
    private $WAFSystem;
    private $CSRF;
    private $data; // хранит массив данных из php://input
    private $maxData = 10000; // ограничение на размер входящего объекта

    private function __construct(WAFSystem $wafsystem)
    {
        $this->WAFSystem = $wafsystem;
        $this->CSRF = CSRF::getInstance($this->WAFSystem);
        $client_ip = $this->WAFSystem->Profile->IP;

        // Блокировка плохих запросов
        if (!$this->isPost()) {
            $this->WAFSystem->Logger->log("Not a POST request");
            $this->endJSON('block');
        }

        $input = file_get_contents('php://input');
        if (($len = strlen($input)) > $this->maxData) {
            $message = "Error: Input data size exceeded (size: $len, max: $this->maxData)";
            $this->WAFSystem->Logger->log($message);
            $this->WAFSystem->GrayList->add($client_ip, $message);
            $this->endJSON('fail');
        }

        $this->data = json_decode($input, true);

        if (empty($this->data)) {
            $message = "Error: JSON-data is empty";
            $this->WAFSystem->Logger->log($message, [static::class]);
            $this->WAFSystem->GrayList->add($client_ip, $message);
            $this->endJSON('fail');
        }

        if (!isset($this->data['func'])) {
            $message = "Error: Param 'func' not found";
            $this->WAFSystem->Logger->log($message, [static::class]);
            $this->WAFSystem->GrayList->add($client_ip, $message);
            $this->endJSON('fail');
        }

        if (!isset($this->data['csrf_token'])) {
            $this->WAFSystem->Logger->log("Error: Param 'csrf_token' not found");
            $this->endJSON('fail');
        }

        if ($this->data['func'] == "load_module") {
            $this->endJSON("no_csrf", [ // доступ без csrf
                "modules" => ["metrika"]
            ]);
        }

        if ($this->data['func'] == "get_param") {
            $this->endJSON("no_csrf", [ // доступ без csrf
                "metrika" => \WAFSystem\Metrika::getInstance($this->WAFSystem)->get("ID"),
                "ip" => $this->WAFSystem->Profile->IP,
                "fp" => $this->WAFSystem->FingerPrint->enabled
            ]);
        }

        try {
            $this->CSRF->validCSRF($this->data['csrf_token']);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->WAFSystem->Logger->log($message, [static::class, $this->data]);
            $this->WAFSystem->GrayList->add($client_ip, $message);
            $this->endJSON('fail', ['message' => $message]);
        }
    }

    public static function getInstance(WAFSystem $wafsystem)
    {
        if (is_null(self::$_instances))
            self::$_instances = new self($wafsystem);

        return self::$_instances;
    }

    public function endJSON($status, $data = [])
    {
        header('Content-type: application/json; charset=utf-8');

        $res = ['status' => $status];
        if (!session_id()) {
            $res = "Critical error: Session session_start() not started.";
            $this->WAFSystem->Logger->log($res, [static::class]);
            echo json_encode($res);
            exit;
        }

        if ($status == 'captcha') {
            $this->WAFSystem->Logger->log("Show captcha");
            $this->setHiddenValue();
        }

        if ($status == 'allow') {
            $this->removeHiddenValue();
        }

        if ($status == 'block') {
            header("HTTP/1.0 403 Forbidden");
        }

        if ($status != 'fail' && $status != 'no_csrf') { // не выдавать ключь для ошибки или данных без ключа
            $csrf_token = $this->CSRF->createCSRF();
        }

        if (isset($this->data['func'])) {
            $res = array_merge([
                'func' => $this->data['func'],
                'csrf_token' => isset($csrf_token) ? $csrf_token : ""
            ], $res);
        }

        if (sizeof($data) > 0)
            $res = array_merge($res, $data);

        echo json_encode($res);
        exit;
    }

    /**
     * Устанавливает ключ, при котором разблокируется запрос на установку метки
     */
    private function setHiddenValue()
    {
        $_SESSION['rndname'] = true;
    }

    private function removeHiddenValue()
    {
        unset($_SESSION['rndname']);
    }

    /**
     * Проверяет наличие ключа разблокировки
     */
    public function isHiddenValue()
    {
        return isset($_SESSION['rndname']);
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
        if (
            !($this->WAFSystem->WhiteListIP->isListed($client_ip)
                || ($this->WAFSystem->IndexBot->enabled && $this->WAFSystem->IndexBot->isIndexbot($client_ip))
            )
        ) {
            $this->WAFSystem->BlackListIP->add($this->WAFSystem->Profile->IP, $message);
        }
    }
}
