<?php

namespace WAFSystem;

/**
 * Класс предназначен для получения инфомации с дополнительных 
 * заголовков прокси-серверов и балансировщиков
 */
class HeaderProxy
{
    public $enabled = false;

    private $Config;
    private $header_ip = 'X-Real-IP';
    private $header_http_version = 'X-Forwarded-Http-Version';
    private $header_proto = 'X-Forwarded-Proto';

    public function __construct(Config $config)
    {
        $this->Config = $config;

        $this->enabled = $this->Config->init('header_proxy', 'enabled', $this->enabled, 'Включает обработку дополнительных заголовков');
        $this->header_ip = $this->Config->init('header_proxy', 'header_ip', $this->header_ip, 'ip-адрес');
        $this->header_http_version = $this->Config->init('header_proxy', 'header_http_version', $this->header_http_version, 'версия HTTP');
        $this->header_proto = $this->Config->init('header_proxy', 'header_protocol', $this->header_proto, 'http или https');
    }

    /**
     * Возвращает IP адрес клиента с учетом прокси-серверов и балансировщиков
     */
    public function getIPAddr()
    {
        $result = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''; // Значение по умолчанию

        if ($this->enabled) {
            $header = $this->header_ip;

            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]); // Берем первый IP из списка

                if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                    throw new \Exception('Value header "'. $header .'" is not IP: ' . $ip);
                }
                return $ip;
            }
        }
        return $result;
    }

    /**
     * Получение протокола (http/https)
     */
    public function getProtocol()
    {
        $result = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : '';
        if ($this->enabled) {
            $header = $this->header_proto;
            if (!empty($_SERVER[$header])) {
                $proto = strtolower($_SERVER[$header]);
                if ($proto === 'https' || $proto === 'on') {
                    return 'https';
                }
                return 'http';
            }
        }
        return $result;
    }

    /**
     * Получает версию HTTP
     */
    public function getHttpVersion()
    {
        $result = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : ''; // основной заголовок
        if ($this->enabled) {
            $header = $this->header_http_version;
            if (!empty($_SERVER[$header])) { // если заголовка не, то считаем, что он равен основному
                $result = $_SERVER[$header];
                if (preg_match('/(\d\.\d)/', $_SERVER[$header], $matches) === 1) {
                    $result = 'HTTP/' . $matches[1];
                }
                return $result;
            }
        }
        return $result;
    }
}
