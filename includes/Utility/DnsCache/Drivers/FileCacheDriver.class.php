<?php

namespace DnsCache;

class FileCacheDriver implements CacheDriverInterface
{
    private $cacheDir;

    public function __construct($cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, '/') . '/';

        if (!file_exists($this->cacheDir) && !mkdir($this->cacheDir, 0777, true)) {
            throw new \RuntimeException("Failed to create cache directory");
        }
    }

    public function get($key)
    {
        $path = $this->getPath($key);
        if (!file_exists($path)) return false;

        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : false;
    }

    public function set($key, array $data, $ttl)
    {
        $path = $this->getPath($key);
        $dir = dirname($path);
        $tmpPath = tempnam($dir, 'tmp_');
        if ($tmpPath === false) {
            throw new \RuntimeException("Failed to create temp file");
        }

        try {
            // Записываем данные с блокировкой
            if (file_put_contents($tmpPath, json_encode($data), LOCK_EX) === false) {
                throw new \RuntimeException("Failed to write to temp file");
            }
            
            // Атомарное перемещение
            if (!rename($tmpPath, $path)) {
                throw new \RuntimeException("Failed to rename temp file");
            }
            
            return true;
        } catch (\Exception $e) {
            @unlink($tmpPath);
            error_log("Cache write error: " . $e->getMessage());
            return false;
        }
    }

    public function delete($key)
    {
        $path = $this->getPath($key);
        return file_exists($path) ? unlink($path) : false;
    }

    public function clear()
    {
        $files = glob($this->cacheDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
        return true;
    }

    public function isAvailable()
    {
        return is_writable($this->cacheDir);
    }

    private function getPath($key)
    {
        // Санитизация ключа (заменяем все не-ASCII символы)
        $safeKey = preg_replace('/[^a-z0-9_\-]/i', '_', $key);

        // Создаем двухуровневую структуру папок
        $subDir = $this->cacheDir . substr(md5($safeKey), 0, 2) . '/';

        // Атомарное создание поддиректории
        if (!file_exists($subDir) && !mkdir($subDir, 0777, true)) {
            throw new \RuntimeException("Failed to create subdirectory: " . $subDir);
        }

        return $subDir . $safeKey . '.json';
    }

    public function setReverseDns($ip, $hostname, $ttl)
    {
        $key = 'rdns_' . md5($ip);
        $data = [
            'hostname' => $hostname,
            'expires' => time() + $ttl,
            'is_negative' => ($hostname === null)
        ];
        return $this->set($key, $data, $ttl);
    }

    public function getReverseDns($ip)
    {
        $key = 'rdns_' . md5($ip);
        $data = $this->get($key);

        if ($data === false) {
            return false; // Нет записи в кэше
        }

        if (!isset($data['expires']) || $data['expires'] < time()) {
            return false; // Запись устарела
        }

        return $data['is_negative'] ? null : $data['hostname'];
    }
}
