<?php

namespace Utility;

/**
 * Класс для кодирования/декодирования строки
 * по аналогии base64
 */
class StrCode
{
    private static $key = "dRM3oMKLdm3Nnj";
    private static $useOpenSSL = null;

    /**
     * Проверяет доступность OpenSSL и выбирает метод шифрования
     */
    private static function initialize()
    {
        if (self::$useOpenSSL === null) {
            self::$useOpenSSL = extension_loaded('openssl') &&
                in_array('aes-256-cbc', openssl_get_cipher_methods());
        }
    }

    /**
     * Шифрует строку с кодовым словом (устаревший метод)
     */
    private static function str_code($str, $passw = "dKM3oMKLdmcnj")
    {
        $salt = "Dn8*#2n!9j=&+";
        $len = strlen($str);
        $gamma = '';
        $n = $len > 100 ? 8 : 2;
        while (strlen($gamma) < $len) {
            $gamma .= substr(pack('H*', sha1($passw . $gamma . $salt)), 0, $n);
        }
        return $str ^ $gamma;
    }

    /**
     * Шифрование через OpenSSL
     */
    private static function opensslEncrypt($str, $passw)
    {
        $method = 'aes-256-cbc';
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));

        // Генерируем ключ из пароля
        $key = hash('sha256', $passw, true);

        $encrypted = openssl_encrypt($str, $method, $key, OPENSSL_RAW_DATA, $iv);

        // Объединяем IV и зашифрованные данные
        return base64_encode($iv . $encrypted);
    }

    /**
     * Дешифрование через OpenSSL
     */
    private static function opensslDecrypt($str, $passw)
    {
        $method = 'aes-256-cbc';
        $data = base64_decode($str);

        $ivLength = openssl_cipher_iv_length($method);
        $iv = substr($data, 0, $ivLength);
        $encrypted = substr($data, $ivLength);

        // Генерируем ключ из пароля
        $key = hash('sha256', $passw, true);

        return openssl_decrypt($encrypted, $method, $key, OPENSSL_RAW_DATA, $iv);
    }

    /** 
     * Кодирует строку 
     */
    public static function Encode($str, $passw = null)
    {
        self::initialize();

        if ($passw === null) {
            $passw = self::$key;
        }

        if (self::$useOpenSSL) {
            return self::opensslEncrypt($str, $passw);
        } else {
            return base64_encode(self::str_code($str, $passw));
        }
    }

    /** 
     * Декодирует сроку 
     */
    public static function Decode($str, $passw = null)
    {
        self::initialize();

        if ($passw === null) {
            $passw = self::$key;
        }

        if (self::$useOpenSSL) {
            return self::opensslDecrypt($str, $passw);
        } else {
            return self::str_code(base64_decode($str), $passw);
        }
    }

    /**
     * Принудительно включает/выключает использование OpenSSL
     */
    public static function setUseOpenSSL($use)
    {
        self::$useOpenSSL = $use;
    }

    /**
     * Проверяет, используется ли OpenSSL
     */
    public static function isUsingOpenSSL()
    {
        self::initialize();
        return self::$useOpenSSL;
    }
}
