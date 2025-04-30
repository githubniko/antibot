<?php

namespace WAFSystem;

abstract class IPList
{
    protected $listFile;
    protected $Logger;

    public function __construct(string $listFile, Logger $logger)
    {
        $this->listFile = $listFile;
        $this->Logger = $logger;
        $this->initListFile();
    }

    /**
     * Проверяет наличие IP в списке
     */
    public function isListed($ip)
    {
        if (!$this->validateIp($ip)) {
            return false;
        }

        return $this->checkIpInFile($ip);
    }

    /**
     * Добавляет IP в список
     */
    public function add($ip, $comment = '')
    {
        if ($this->isListed($ip)) {
            return;
        }

        $entry = $this->formatEntry($ip, $comment);
        $this->saveEntry($entry);

        $this->Logger->logMessage("IP added to list: " . $ip . " (" . static::class . ")");
    }

    public static function isIPv6($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    protected function checkIpInFile($ip)
    {
        if (!file_exists($this->listFile)) {
            return false;
        }

        $file = fopen($this->listFile, 'r');
        if (!$file) {
            $this->Logger->logMessage("Error reading file: " . $this->listFile);
            return false;
        }

        $ipBinary = inet_pton($ip);
        $result = false;

        while (($line = fgets($file)) !== false) {
            $lineIp = $this->extractIpFromLine($line);
            if ($lineIp && inet_pton($lineIp) === $ipBinary) {
                $result = true;
                break;
            }
        }

        fclose($file);
        return $result;
    }

    protected function initListFile()
    {
        if (!file_exists($this->listFile)) {
            $defaultContent = $this->getDefaultFileContent();
            file_put_contents($this->listFile, $defaultContent);
            $this->Logger->logMessage("New list file created: " . $this->listFile);

            $this->eventInitListFile();
        }
    }

    protected function validateIp($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    protected function formatEntry($ip, $comment)
    {
        return $ip . (empty($comment) ? '' : " # " . trim($comment)) . PHP_EOL;
    }

    protected function saveEntry($entry)
    {
        $file = fopen($this->listFile, 'a');
        if (flock($file, LOCK_EX)) {
            fwrite($file, $entry);
            flock($file, LOCK_UN);
        }
        fclose($file);
    }

    protected function extractIpFromLine($line)
    {
        $line = trim(preg_replace('/#.*$/', '', $line));
        return $this->validateIp($line) ? $line : null;
    }

    abstract protected function getDefaultFileContent();
    abstract protected function eventInitListFile(); // Событие срабатывает при создании файла листа
}
