<?php

namespace Session;

use Cache\CacheDriverInterface;

class SessidStorage
{
    /**
     * Имя куки для хранения ID сессии
     */
    const COOKIE_NAME = 'AWSESSID';

    /**
     * Длина генерируемого ID сессии
     */
    const SESSION_ID_LENGTH = 32;

    /**
     * @var CacheDriverInterface
     */
    private $cacheDriver;

    /**
     * @var string|null
     */
    private $sessionId;

    /**
     * @var array|null
     */
    public $data;

    /**
     * @param CacheDriverInterface $cacheDriver
     */
    public function __construct($cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;

        // Получаем существующий ID сессии из куки или генерируем новый
        $this->sessionId = $this->getSessionIdFromCookie();

        if ($this->sessionId === null) {
            $this->sessionId = $this->generateSessionId();
            $this->data = array();
        } else {
            // Загружаем данные сессии
            $this->data = $this->cacheDriver->get($this->sessionId);

            if ($this->data === false || $this->data === null) {
                $this->data = array();
            }
        }

        // Устанавливаем куку
        $this->setSessionCookie($this->sessionId);
    }

    public function __destruct()
    {
        try {
            $this->save();
        } catch (\Exception $e) {
            error_log('AWSession error: ' . $e->getMessage());
        }
    }

    /**
     * Получает указатель на объект данных
     */
    public function &getCursorData()
    {
        return $this->data;
    }

    /**
     * Возвращет название куки для сессии
     */
    public function session_name()
    {
        return self::COOKIE_NAME;
    }

    /**
     * Устанавливает значение по аналогии с куками
     */
    public function set($key, $value, $ttl)
    {
        $this->data[$key] = [
            'value' => $value,
            'expire' => time() + $ttl,
            'created_at' => time()
        ];
    }

    /**
     * Возвращает значение, если не истек срок хранения
     */
    public function get($key)
    {
        if (isset($this->data[$key]["expire"]) && $this->data[$key]["expire"] < time()) {
            $this->delete($key);
            return null;
        }
        return $this->data[$key]["value"];
    }

    /**
     * Удалить значение из сессии
     * 
     * @param string $key
     * @return void
     * @throws \RuntimeException
     */
    public function delete($key)
    {
        unset($this->data[$key]);
    }

    /**
     * Сохранить данные сессии
     * 
     * @return bool
     * @throws \RuntimeException
     */
    public function save()
    {
        if ($this->sessionId === null) {
            throw new \RuntimeException('Session not started');
        }

        $result = $this->cacheDriver->set($this->sessionId, $this->data);

        if (!$result) {
            throw new \RuntimeException('Failed to save session data');
        }

        return true;
    }

    /**
     * Уничтожить сессию
     * 
     * @return bool
     * @throws \RuntimeException
     */
    public function destroy()
    {
        if ($this->sessionId !== null) {
            // Удаляем данные из кэша
            $this->cacheDriver->delete($this->sessionId);

            // Удаляем куку
            $this->deleteSessionCookie();

            // Очищаем локальные данные
            $this->sessionId = null;
            $this->data = null;
        }

        return true;
    }

    /**
     * Регенерировать ID сессии
     * 
     * @return bool
     * @throws \RuntimeException
     */
    public function regenerateId()
    {
        $oldSessionId = $this->sessionId;
        $olddata = $this->data;

        // Генерируем новый ID
        $newSessionId = $this->generateSessionId();

        // Сохраняем данные под новым ID
        $this->sessionId = $newSessionId;
        $this->data = $olddata;

        if (!$this->save()) {
            // Восстанавливаем старый ID в случае ошибки
            $this->sessionId = $oldSessionId;
            $this->data = $olddata;
            return false;
        }

        // Удаляем старую сессию
        $this->cacheDriver->delete($oldSessionId);

        // Обновляем куку
        $this->setSessionCookie($newSessionId);

        return true;
    }

    /**
     * Получить ID текущей сессии
     * 
     * @return string|null
     */
    public function getId()
    {
        return $this->sessionId;
    }

    /**
     * Генерирует случайный ID сессии
     * 
     * @return string
     */
    private function generateSessionId()
    {
        if (function_exists('random_bytes')) {
            // PHP 7+
            return bin2hex(random_bytes(self::SESSION_ID_LENGTH / 2));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            // PHP 5.3+ с OpenSSL
            return bin2hex(openssl_random_pseudo_bytes(self::SESSION_ID_LENGTH / 2));
        } else {
            // Fallback для очень старых версий
            return $this->fallbackGenerateSessionId();
        }
    }

    /**
     * Fallback генерация ID для PHP без cryptographically secure random
     * 
     * @return string
     */
    private function fallbackGenerateSessionId()
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < self::SESSION_ID_LENGTH; $i++) {
            $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    /**
     * Получает ID сессии из куки
     * 
     * @return string|null
     */
    private function getSessionIdFromCookie()
    {
        return isset($_COOKIE[self::COOKIE_NAME]) ? $_COOKIE[self::COOKIE_NAME] : null;
    }

    /**
     * Устанавливает куку с ID сессии
     * 
     * @param string $sessionId
     * @return void
     */
    private function setSessionCookie($sessionId)
    {
        // Сессионная кука (без времени жизни, удаляется при закрытии браузера)
        if (PHP_VERSION_ID >= 70300) {
            // PHP 7.3+ поддерживает массив опций
            setcookie(self::COOKIE_NAME, $sessionId, array(
                'expires' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ));
        } else {
            // PHP 5.6 - 7.2
            setcookie(self::COOKIE_NAME, $sessionId, 0, '/', '', false, true);
        }
    }

    /**
     * Удаляет куку сессии
     * 
     * @return void
     */
    private function deleteSessionCookie()
    {
        if (PHP_VERSION_ID >= 70300) {
            // PHP 7.3+ поддерживает массив опций
            setcookie(self::COOKIE_NAME, '', array(
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ));
        } else {
            // PHP 5.6 - 7.2
            setcookie(self::COOKIE_NAME, '', time() - 3600, '/', '', false, true);
        }

        unset($_COOKIE[self::COOKIE_NAME]);
    }

    /**
     * Магический метод для удаления свойства
     * 
     * @param string $name
     */
    public function __unset($name)
    {
        $this->delete($name);
    }
}
