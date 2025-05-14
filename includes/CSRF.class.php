<?php

namespace WAFSystem;

class CSRF {

    public function getCSRF()
    {
        if (!$this->isCSRF())
            $this->createCSRF();
        return $_SESSION['csrf_token'];
    }

    public function createCSRF()
    {
        return $_SESSION['csrf_token'] = $this->genKey();
    }

    public function isCSRF()
    {
        return !empty($_SESSION['csrf_token']);
    }
/**
 * Проверяет, пустой csrf_token
 */
    public function emptyCSRFRequest($csrf_token)
    {
        return isset($csrf_token) && empty($csrf_token);
    }

    public function validCSRF($csrf_token)
    {
        if (!$this->isCSRF())
            return false;

        if ($this->getCSRF() != $csrf_token)
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

    # альтернатива random_bytes() для PHP < 7.0.0
    private function random_bytes_php5($length) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if ($strong === true) {
                return $bytes;
            }
        }
        // Если openssl недоступен, можно использовать менее безопасные варианты
        throw new \RuntimeException('Не удалось сгенерировать криптографически безопасные данные');
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
}