<?php

namespace Utility;

class Curl
{
    private $timeout; // время ожидания
    private $verifySsl; // проверять SSL
    private $curlOptions;

    public function __construct($timeout = 10, $verifySsl = true)
    {
        $this->timeout = $timeout;
        $this->verifySsl = $verifySsl;
        $this->curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
            CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
            CURLOPT_ENCODING => '', // автоматическое сжатие
            CURLOPT_USERAGENT => 'AntibotWAF/2.0',
            CURLOPT_FAILONERROR => true,
        ];
    }

    /**
     * Выполняет HTTP-запрос
     * 
     * @param string $url URL для запроса
     * @param array $headers Дополнительные заголовки
     * @return string Тело ответа
     * @throws \RuntimeException При ошибках запроса
     */
    public function fetch($url, $headers = [])
    {
        $len = 51; // длина сокращения url (для логирования)
        $urlShort = strlen($url) <= $len ? substr($url, 0, $len). '...' : $url;
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid URL provided ". $urlShort);
        }

        $ch = curl_init();

        try {
            $options = $this->curlOptions + [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $headers,
            ];

            curl_setopt_array($ch, $options);

            $response = curl_exec($ch);

            if ($response === false) {
                $error = curl_error($ch);
                $errno = curl_errno($ch);
                throw new \RuntimeException(
                    sprintf('curl %s: [%d] %s', $urlShort, $errno, $error),
                    $errno
                );
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode >= 400) {
                throw new \RuntimeException(
                    sprintf('curl %s HTTP error %d: %s', $urlShort, $httpCode, $response),
                    $httpCode
                );
            }

            return $response;
        } finally {
            if (is_resource($ch)) {
                curl_close($ch);
            }
        }
    }
}
