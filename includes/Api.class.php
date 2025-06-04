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

    public function __construct(WAFSystem $wafsystem)
    {
        $this->WAFSystem = $wafsystem;
        $this->CSRF = new CSRF();
        $client_ip = $this->WAFSystem->Profile->IP;

        $input = file_get_contents('php://input');
        if (($len=strlen($input)) > $this->maxData) {
            $message = "Error: Input data size exceeded (size: $len, max: $this->maxData)";
            $this->WAFSystem->Logger->log($message);
            $this->WAFSystem->GrayList->add($client_ip, $message);
            $this->endJSON('fail');
        }
        $this->data = json_decode($input, true);

        if (empty($this->data)) {
            $message = "Error: Data is empty";
            $this->WAFSystem->Logger->log($message, [static::class]);
            $this->WAFSystem->GrayList->add($client_ip, $message);
            $this->endJSON('fail');
        }

        if (!isset($this->data['func'])) {
            $message = "Error: Value 'func' is not set";
            $this->WAFSystem->Logger->log($message, [static::class]);
            $this->WAFSystem->GrayList->add($client_ip, $message);
            $this->endJSON('fail');
        }

        if ($this->data['func'] == 'csrf_token') {
            $this->WAFSystem->Logger->rotateIfNeeded(); // делаем ротация логов, пока пользователь ожидает проверку
            $this->endJSON('ok');
        }

        try {
            $this->CSRF->validCSRF($this->data['csrf_token'], $_SERVER['REQUEST_METHOD']);
        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->WAFSystem->Logger->log($message, [static::class]);
            $this->WAFSystem->GrayList->add($client_ip, $message);
            $this->endJSON('captcha');
        }
    }

    public static function getInstance(WAFSystem $wafsystem)
    {

        if (is_null(self::$_instances)) {
            self::$_instances = new self($wafsystem);
        }
        return self::$_instances;
    }

    public function endJSON($status, $data = [])
    {
        $res = ['status' => $status];
        if (!session_id()) {
            $res = "Critical error: Session session_start() not started.";
            $this->WAFSystem->Logger->log($res, [static::class]);
            echo json_encode($res);
            exit;
        }

        if ($status == 'captcha') {
            $this->WAFSystem->Logger->log("Show captcha");
        }

        $res = array_merge([
            'func' => $this->data['func'],
            'csrf_token' => $this->CSRF->createCSRF()
        ], $res);

        if (sizeof($data) > 0)
            $res = array_merge($res, $data);

        echo json_encode($res);
        exit;
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
