<?php

/**
 * Проверяет, является ли IP-адрес выходным узлом Tor
 * Поддерживает IPv4/IPv6, кэширование, fallback-методы и таймауты
 * 
 * @param string $ip Проверяемый IP-адрес
 * @param array $options Дополнительные настройки:
 *      - 'cache_ttl' => int Время жизни кэша в секундах (по умолчанию 3600)
 *      - 'timeout' => int Таймаут запроса в секундах (по умолчанию 2)
 *      - 'fallback' => bool Использовать DNS если HTTP-метод недоступен (по умолчанию true)
 * @return bool
 * @throws RuntimeException Если проверка невозможна
 */
function isTor($ip, $options = [])
{
    // Нормализация параметров
    $options = array_merge([
        'cache_ttl' => 3600,
        'timeout' => 2,
        'fallback' => true,
    ], $options);

    // Валидация IP
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        throw new InvalidArgumentException("Invalid IP address: $ip");
    }

    $isIPv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    if ($isIPv6) 
        $ip = expandIPv6($ip);


    $result = false;
    $dnsMethodFailed = false;

    // Fallback на HTTP-метод если DNS не сработал
    if (!$dnsMethodFailed) {
        try {
            $result = checkViaHttp($ip, $options['timeout']);
        } catch (Exception $e) {
            $dnsMethodFailed = true;
        }
    }

    // Пытаемся использовать DNS-метод (предпочтительный)
    if ($dnsMethodFailed && $options['fallback']) {
        try {
            $result = checkViaDns($ip, $options['timeout']);
        } catch (Exception $e) {
            throw new Exception("All Tor check methods failed: (" . $e->getMessage() . ")");
        }
    }

    return $result;
}

/**
 * Проверка через DNS (IPv4)
 */
function checkViaDns($ip, $timeout)
{
    $isIPv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    if($isIPv6)
        throw new Exception("Unable to check IPv6 via DNS method");

    // Настройка таймаута
    $originalTimeout = ini_get('default_socket_timeout');
    ini_set('default_socket_timeout', $timeout);

    try {
        $reversedIp = implode('.', array_reverse(explode('.', $ip)));
        $dnsQuery = $reversedIp . '.dnsel.torproject.org';

        try {
            $records = dns_get_record($dnsQuery, DNS_A);
            
            if (!empty($records) && isset($records[0]['ip']) && $records[0]['ip'] === '127.0.0.2') {
                return true;
            }
        } catch (Exception $e) {
            throw "Error: DNS is not available ";
        }

        return false;
    } finally {
        ini_set('default_socket_timeout', $originalTimeout);
    }
}

/**
 * Проверка через HTTP (fallback метод)
 */
function checkViaHttp($ip, $timeout)
{
    global $DOCUMENT_ROOT, $HTTP_ANTIBOT_PATH;

    $url = 'https://www.dan.me.uk/torlist/?exit';
    $cacheFile = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . 'tor_exit_nodes.cache';

    // Проверка кэша
    if (file_exists($cacheFile))
    {
        $cacheTime = filemtime($cacheFile);
        if (time() - $cacheTime < 3600) { // 1 час кэш
            $torIps = file($cacheFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            return in_array($ip, $torIps);
        }
    }

    // Загрузка списка с таймаутом
    $ctx = stream_context_create([
        'http' => ['timeout' => $timeout],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
    ]);

    $torIps = @file_get_contents($url, false, $ctx);
    if ($torIps === false) {
        throw new Exception("Failed to fetch Tor exit node list");
    }

    // Сохранение в кэш
    file_put_contents($cacheFile, $torIps);
    $torIpList = explode("\n", trim($torIps));

    return in_array($ip, $torIpList);
}

/**
 * Расширяет сокращенную запись IPv6 адреса в полную форму
 * 
 * @param string $ip IPv6 адрес в любом формате (с сокращениями или без)
 * @param bool $validate Включить валидацию адреса (по умолчанию true)
 * @param bool $preserveCase Сохранять оригинальный регистр (по умолчанию false - приводит к lowercase)
 * @return string Полная 8-сегментная форма IPv6
 * @throws InvalidArgumentException Если передан некорректный IPv6 (при включенной валидации)
 */
function expandIPv6($ip, $validate = true, $preserveCase = false)
{
    // Удаляем квадратные скобки если есть (для URI формата [::1])
    $ip = trim($ip, '[]');
    
    // Сохраняем зонный индекс если есть (fe80::1%eth0)
    $zoneIndex = '';
    if (($pos = strpos($ip, '%')) !== false) {
        $zoneIndex = substr($ip, $pos);
        $ip = substr($ip, 0, $pos);
    }

    // Валидация IPv6
    if ($validate && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        throw new InvalidArgumentException("Некорректный IPv6 адрес: " . $ip . $zoneIndex);
    }

    // Приводим к нижнему регистру если не требуется сохранять регистр
    if (!$preserveCase) {
        $ip = strtolower($ip);
    }

    // Обработка специальных случаев
    if ($ip === '') {
        return '0000:0000:0000:0000:0000:0000:0000:0000' . $zoneIndex;
    }

    // Разбиваем на части по ::
    $parts = explode('::', $ip, 2);
    
    // Если есть сокращение ::
    if (count($parts) === 2) {
        list($left, $right) = $parts;
        
        $leftSegments = $left !== '' ? explode(':', $left) : [];
        $rightSegments = $right !== '' ? explode(':', $right) : [];
        
        $missing = 8 - (count($leftSegments) + count($rightSegments));
        
        if ($missing < 0) {
            throw new InvalidArgumentException("Слишком много сегментов в IPv6: " . $ip);
        }
        
        $fullSegments = array_merge(
            $leftSegments,
            array_fill(0, $missing, '0000'),
            $rightSegments
        );
    } else {
        // Если нет сокращения ::
        $fullSegments = explode(':', $ip);
    }

    // Дополняем каждый сегмент нулями слева до 4 символов
    $fullSegments = array_map(function($segment) use ($preserveCase) {
        if ($segment === '') return '0000';
        
        // Проверка на некорректные символы
        if (!preg_match('/^[0-9a-f]{1,4}$/i', $segment)) {
            throw new InvalidArgumentException("Некорректный сегмент IPv6: " . $segment);
        }
        
        return str_pad($segment, 4, '0', STR_PAD_LEFT);
    }, $fullSegments);

    // Проверка общего количества сегментов
    if (count($fullSegments) !== 8) {
        throw new InvalidArgumentException("Некорректное количество сегментов в IPv6: " . $ip);
    }

    // Собираем полный адрес
    $expanded = implode(':', $fullSegments) . $zoneIndex;
    
    // Финалная проверка длины (без зонного индекса)
    if (strlen(str_replace(':', '', $expanded)) !== 32 && strpos($expanded, '%') === false) {
        throw new InvalidArgumentException("Некорректная длина IPv6 адреса: " . $expanded);
    }

    return $expanded;
}