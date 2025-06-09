<?php

namespace Utility;

/**
 * Класс для работы с IP-адресами (IPv4/IPv6)
 * Поддерживает проверки: одиночный IP, CIDR, диапазон
 */
class Network
{
    /**
     * Проверяет, входит ли IP в указанный диапазон (IP, CIDR или диапазон)
     * @param string $ip
     * @param string $range
     * @return bool
     */
    public static function isInRange($ip, $range)
    {
        // Быстрая проверка строки (без учета регистра для IPv6)
        if (strcasecmp($ip, $range) === 0) {
            return true;
        }

        $ipBinary = self::ipToBinary($ip);
        if ($ipBinary === false) {
            return false;
        }

        // Проверка на одиночный IP
        $rangeBinary = self::ipToBinary($range);
        if ($rangeBinary !== false) {
            return $ipBinary === $rangeBinary;
        }

        // Обработка CIDR (192.168.1.0/24)
        if (strpos($range, '/') !== false) {
            if (!self::isValidCidr($range)) {
                return false;
            }
            return self::checkCidrRange($ipBinary, $range);
        }

        // Обработка диапазона (192.168.1.1-192.168.1.100)
        if (strpos($range, '-') !== false) {
            
            return self::checkIpRange($ipBinary, $range);
        }

        return false;
    }

    /**
     * Проверяет валидность CIDR-нотации
     * @param string $cidr
     * @return bool
     */
    public static function isValidCidr($cidr)
    {
        if (strpos($cidr, '/') === false) {
            return false;
        }

        list($network, $netmask) = explode('/', $cidr, 2);
        
        $networkBinary = self::ipToBinary($network);
        if ($networkBinary === false) {
            return false;
        }

        if (!ctype_digit($netmask)) {
            return false;
        }

        $maxNetmask = strlen($networkBinary) * 8;
        $netmask = (int)$netmask;
        
        return $netmask >= 0 && $netmask <= $maxNetmask;
    }

    /**
     * Нормализует CIDR (приводит IP-часть к стандартному виду)
     * @param string $cidr
     * @return string|false
     */
    public static function normalizeCidr($cidr)
    {
        if (!self::isValidCidr($cidr)) {
            return false;
        }

        list($network, $netmask) = explode('/', $cidr, 2);
        $networkBinary = self::ipToBinary($network);
        $normalizedNetwork = inet_ntop($networkBinary);

        return $normalizedNetwork . '/' . $netmask;
    }

    /**
     * Возвращает версию IP для CIDR: 4 (IPv4), 6 (IPv6) или false
     * @param string $cidr
     * @return int|false
     */
    public static function getCidrVersion($cidr)
    {
        if (!self::isValidCidr($cidr)) {
            return false;
        }

        list($network) = explode('/', $cidr, 2);
        return strpos($network, ':') === false ? 4 : 6;
    }

    /**
     * Проверяет CIDR-диапазон
     * @param string $ipBinary
     * @param string $cidrRange
     * @return bool
     */
    private static function checkCidrRange($ipBinary, $cidrRange)
    {
        list($network, $netmask) = explode('/', $cidrRange, 2);
        
        $networkBinary = self::ipToBinary($network);
        if ($networkBinary === false || strlen($networkBinary) !== strlen($ipBinary)) {
            return false;
        }

        $netmask = (int)$netmask;
        $maxNetmask = strlen($ipBinary) * 8;

        // Полная маска (/32 или /128) — простое сравнение
        if ($netmask === $maxNetmask) {
            return $ipBinary === $networkBinary;
        }

        $mask = self::createBinaryMask($netmask, strlen($ipBinary));
        return ($ipBinary & $mask) === ($networkBinary & $mask);
    }

    /**
     * Проверяет диапазон IP (начальный-конечный)
     * @param string $ipBinary
     * @param string $ipRange
     * @return bool
     */
    private static function checkIpRange($ipBinary, $ipRange)
    {
        list($startIp, $endIp) = explode('-', $ipRange, 2);
        
        $startBinary = self::ipToBinary($startIp);
        $endBinary = self::ipToBinary($endIp);
        if ($startBinary === false || $endBinary === false || strlen($startBinary) !== strlen($ipBinary)) {
            return false;
        }

        return strcmp($ipBinary, $startBinary) >= 0 && strcmp($ipBinary, $endBinary) <= 0;
    }

    /**
     * Конвертирует IP в бинарный формат
     * @param string $ip
     * @return string|false
     */
    private static function ipToBinary($ip)
    {
        $ip = trim($ip);
        $binary = @inet_pton($ip);
        return $binary !== false ? $binary : false;
    }

    /**
     * Создает бинарную маску для CIDR
     * @param int $netmask
     * @param int $bytesLength
     * @return string
     */
    private static function createBinaryMask($netmask, $bytesLength)
    {
        $mask = '';
        $fullBytes = $netmask >> 3;
        $remainingBits = $netmask & 7;

        if ($fullBytes > 0) {
            $mask .= str_repeat("\xFF", $fullBytes);
        }

        if ($remainingBits > 0) {
            $mask .= chr(0xFF << (8 - $remainingBits));
        }

        return str_pad($mask, $bytesLength, "\x00");
    }
}