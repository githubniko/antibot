<?php

namespace DnsCache;

class RedisCacheDriver implements CacheDriverInterface
{
    private $redis;
    private $prefix;
    private $isAvailable = false;

    public function __construct($host = '127.0.0.1', $port = 6379, $prefix = 'dns:')
    {
        // Проверка наличия расширения Redis
        if (!extension_loaded('redis')) {
            throw new \RuntimeException(
                'PHP Redis extension is not installed. ' .
                    'Install it with: pecl install redis ' .
                    'and add "extension=redis.so" to php.ini'
            );
        }

        // Проверка существования класса Redis
        if (!class_exists('Redis')) {
            throw new \RuntimeException(
                'Redis class not found. ' .
                    'Make sure Redis extension is properly installed ' .
                    'and enabled in php.ini'
            );
        }

        $this->redis = new \Redis();
        $this->prefix = $prefix;

        try {
            $this->isAvailable = $this->redis->connect($host, $port, 2.5); // 2.5 sec timeout
            if (!$this->isAvailable) {
                throw new \RuntimeException('Redis connection failed');
            }
        } catch (\Exception $e) {
            $this->isAvailable = false;
            throw new \RuntimeException(
                'Cannot connect to Redis server: ' . $e->getMessage() .
                    ' (Host: ' . $host . ':' . $port . ')'
            );
        }
    }

    public function isAvailable()
    {
        if (!$this->isAvailable) {
            return false;
        }

        try {
            return $this->redis->ping() === '+PONG';
        } catch (\Exception $e) {
            $this->isAvailable = false;
            return false;
        }
    }

    public function get($key)
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $data = $this->redis->get($this->prefix . $key);
            return $data ? json_decode($data, true) : null;
        } catch (\Exception $e) {
            $this->isAvailable = false;
            return null;
        }
    }

    public function set($key, array $data, $ttl)
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            return $this->redis->setex(
                $this->prefix . $key,
                $ttl,
                json_encode($data)
            );
        } catch (\Exception $e) {
            $this->isAvailable = false;
            return false;
        }
    }

    public function delete($key)
    {
        return $this->redis->del($this->prefix . $key);
    }

    public function clear()
    {
        $keys = $this->redis->keys($this->prefix . '*');
        return $this->redis->del($keys);
    }

    public function setReverseDns($ip, $hostname, $ttl)
    {
        try {
            $value = ($hostname === null) ? 'NXDOMAIN' : $hostname;
            return $this->redis->setex(
                $this->prefix . 'rdns:' . $ip,
                $ttl,
                $value
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getReverseDns($ip)
    {
        try {
            $result = $this->redis->get($this->prefix . 'rdns:' . $ip);

            if ($result === false) {
                return false; // Нет записи в кэше
            }

            return $result === 'NXDOMAIN' ? null : $result;
        } catch (\Exception $e) {
            return false; // При ошибках считаем, что записи нет
        }
    }
}
