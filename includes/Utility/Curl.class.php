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

        # Указываем CA-сертификат для CGI режима
        if (PHP_SAPI != 'apache2handler') {
            $caPaths = [
                '/etc/ssl/certs/ca-certificates.crt',    // Ubuntu/Debian
                '/etc/pki/tls/certs/ca-bundle.crt',      // RHEL/CentOS
                '/usr/local/etc/openssl/cert.pem',        // macOS (Homebrew)
            ];

            foreach ($caPaths as $path) {
                if ($this->fileExistsBeyondOpenBasedir($path)) {
                    $this->curlOptions[CURLOPT_CAINFO] = $path;
                    break;
                }
            }
        }
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
        $urlShort = strlen($url) <= $len ? substr($url, 0, $len) . '...' : $url;

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("Invalid URL provided " . $urlShort);
        }

        $ch = curl_init();
        $verboseStream = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_STDERR, $verboseStream);

        try {
            $options = $this->curlOptions + [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $headers,
            ];

            curl_setopt_array($ch, $options);

            $response = curl_exec($ch);

            if ($response === false) {
                rewind($verboseStream);
                $debug = stream_get_contents($verboseStream);

                $error = curl_error($ch);
                $errno = curl_errno($ch);
                throw new \RuntimeException(
                    sprintf("curl %s: [%d] %s\n" . $debug, $urlShort, $errno, $error),
                    $errno
                );
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpCode >= 400) {
                rewind($verboseStream);
                $debug = stream_get_contents($verboseStream);
                throw new \RuntimeException(
                    sprintf("curl %s HTTP error %d: %s\n" . $debug, $urlShort, $httpCode, $response),
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

    private function fileExistsBeyondOpenBasedir($path) {
        // Попробуем через is_readable (иногда обходит ограничения)
        if (is_readable($path)) return true;
        
        // Попробуем через команду shell (если разрешено exec)
        if (function_exists('exec')) {
            exec("[ -r '$path' ] && echo 1 || echo 0", $output);
            if (!empty($output) && $output[0] == '1') return true;
        }
        
        // Попробуем через curl (если URL-доступен)
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            $headers = @get_headers($path);
            return $headers && strpos($headers[0], '200') !== false;
        }
        
        return false;
    }
}
