<?php

namespace WAFSystem;

class CSRF
{
    private $csrf_token_key = 'csrf_tokens'; // название массива токенов
    private $expireTime = 3600; // время жизни токена
    private $tokenPattern = '/^[a-f0-9]{64}$/'; // Шаблон валидации токена (64 hex-символа)

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \RuntimeException('Session is not active');
        }

        if (!isset($_SESSION[$this->csrf_token_key])) {
            $_SESSION[$this->csrf_token_key] = [];
        }
    }

    public function createCSRF()
    {
        $this->cleanExpiredTokens(); // Очищаем устаревшие токены
        $token = $this->genKey();
        $_SESSION[$this->csrf_token_key][$token] = [
            'expire' => time() + $this->expireTime,
            'session_id' => session_id(),
            'created_at' => time()
        ];

        return $token;
    }

    public function validCSRF($csrf_token, $requestMethod = 'POST')
    {
        $this->checkSessionAndCookies();

        if (strtoupper($requestMethod) === 'GET') {
            throw new \Exception('CSRF-tokens should not be used in GET requests');
        }

        // Проверка формата (64 hex-символа для 32 байт)
        if (!preg_match($this->tokenPattern, $csrf_token)) {
            throw new \Exception('Error: Invalid CSRF-token format');
        }

        if (!isset($_SESSION[$this->csrf_token_key][$csrf_token])) {
            throw new \Exception('Error: CSRF-token not found. Possible reasons: cookies disabled, session expired, or token already used');
        }

        $tokenData = $_SESSION[$this->csrf_token_key][$csrf_token];

        if ($tokenData['expire'] < time()) {
            unset($_SESSION[$this->csrf_token_key][$csrf_token]);
            throw new \Exception('Error: CSRF-token expired');
        }

        if ($tokenData['session_id'] !== session_id()) {
            unset($_SESSION[$this->csrf_token_key][$csrf_token]);
            throw new \Exception('Error: Session mismatch. Possible session hijacking attempt');
        }

        // Удаляем токен после успешной проверки
        unset($_SESSION[$this->csrf_token_key][$csrf_token]);
        return true;
    }

    /**
     * Удаляет CSRF-токен
     * Возвращает true в случае успеха
     */
    public function removeCSRF($csrf_token)
    {
        if (!preg_match($this->tokenPattern, $csrf_token)) {
            throw new \InvalidArgumentException('Invalid token format');
        }

        unset($_SESSION[$this->csrf_token_key][$csrf_token]);
        return true;
    }

    /**
     * Возвращает все активные CSRF-токены (только для отладки)
     */
    public function debugGetTokens()
    {
        $this->cleanExpiredTokens();
        return $_SESSION[$this->csrf_token_key];
    }

    # альтернатива random_bytes() для PHP < 7.0.0
    private function random_bytes_php5($length)
    {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if ($strong === true) {
                return $bytes;
            }
            trigger_error('OpenSSL produced non-strong bytes', E_USER_WARNING);
        }

        $bytes = '';
        for ($i = 0; $i < $length; $i++) {
            $bytes .= chr(mt_rand(0, 255));
        }
        trigger_error('Used fallback random generator (not cryptographically secure)', E_USER_WARNING);

        return $bytes;
    }

    # генерирует случайный код
    private function genKey()
    {
        if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
            return bin2hex(random_bytes(32));
        } else {
            return bin2hex($this->random_bytes_php5(32));
        }
    }

    /**
     * Очищает устаревшие токены
     */
    private function cleanExpiredTokens()
    {
        foreach ($_SESSION[$this->csrf_token_key] as $token => $data) {
            if ($data['expire'] < time()) {
                unset($_SESSION[$this->csrf_token_key][$token]);
            }
        }
    }

    /**
     * Проверяет активность сессии и поддержку кук
     * @throws \RuntimeException
     */
    private function checkSessionAndCookies()
    {
        // Проверка активности сессии
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \RuntimeException('Session is not active');
        }

        // Проверка поддержки кук
        if (empty($_COOKIE[session_name()])) {
            throw new \RuntimeException('Cookies are disabled, or session cookie not set');
        }
    }
}
