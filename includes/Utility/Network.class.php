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
     * Проверяет вхождение ip в CIDR-диапазон
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

    /**
     * Возвращает mix-max значения CIDR
     */
    public static function ipToRange($ipOrCidr)
    {
        if (strpos($ipOrCidr, ':') !== false) {
            // Обработка IPv6
            return self::ipv6ToRange($ipOrCidr);
        } else {
            // Обработка IPv4
            return self::ipv4ToRange($ipOrCidr);
        }
    }

    /**
     * Возвращает mix-max значения CIDR для IPv4
     */
    private static function ipv4ToRange($ipOrCidr)
    {
        if (self::isValidCidr($ipOrCidr) !== false) {
            list($subnet, $mask) = explode('/', $ipOrCidr);
            $ip_beg = ip2long($subnet) & (~((1 << (32 - $mask)) - 1));
            $ip_end = $ip_beg + (1 << (32 - $mask)) - 1;
        } else {
            $ip_beg = $ip_end = ip2long($ipOrCidr);
        }

        return [
            'is_ipv6' => 0,
            'beg' => pack('N', $ip_beg),  // 4-байтовое представление
            'end' => pack('N', $ip_end)
        ];
    }

    /**
     * Возвращает mix-max значения CIDR для IPv6
     */
    private static function ipv6ToRange($ipOrCidr)
    {
        if (self::isValidCidr($ipOrCidr) !== false) {
            list($subnet, $mask) = explode('/', $ipOrCidr);
            $binary = inet_pton($subnet);

            // Вычисляем диапазон для IPv6 CIDR
            $mask_bits = str_repeat('f', $mask >> 2);
            switch ($mask % 4) {
                case 1:
                    $mask_bits .= '8';
                    break;
                case 2:
                    $mask_bits .= 'c';
                    break;
                case 3:
                    $mask_bits .= 'e';
                    break;
            }
            $mask_bits = str_pad($mask_bits, 32, '0');
            $mask_bin = pack('H*', $mask_bits);

            $ip_beg = $binary & $mask_bin;
            $ip_end = $binary | ~$mask_bin;
        } else {
            $ip_beg = $ip_end = inet_pton($ipOrCidr);
        }

        return [
            'is_ipv6' => 1,
            'beg' => $ip_beg,
            'end' => $ip_end
        ];
    }

    /**
     * Валидация номера автономной системы (ASN)
     * @param string|int $asn Проверяемый ASN (может быть строкой или числом)
     * @return bool Возвращает true если ASN валиден
     */
    public static function validateASN($asn)
    {
        // Приводим к строке и удаляем возможные префиксы
        $asnStr = (string)$asn;
        $asnStr = strtoupper($asnStr);

        // Удаляем префикс "AS" если есть
        if (strpos($asnStr, 'AS') === 0) {
            $asnStr = substr($asnStr, 2);
        }

        // Проверяем числовой формат
        if (!ctype_digit($asnStr)) {
            return false;
        }

        // Преобразуем в число для проверки диапазонов
        $asnNumber = (int)$asnStr;

        // Основные диапазоны ASN:
        // 0 - зарезервирован
        // 1-64495 - публичные ASN
        // 64496-64511 - зарезервировано для документации (RFC5398)
        // 64512-65534 - частные ASN (RFC6996)
        // 65535 - зарезервирован
        // 65536-4294967295 - 32-битные ASN (4-байтовые)

        if ($asnNumber === 0 || $asnNumber === 65535) {
            return false; // Зарезервированные значения
        }

        if ($asnNumber >= 1 && $asnNumber <= 4294967295) {
            return true;
        }

        return false;
    }
}
