<?php

namespace WAFSystem;

class Logger
{
    private $config;
    private $profile;

    private $logBasePath;
    private $logFile;
    private $debugEnabled;
    private $rayIDGenerator;

    public function __construct(Config $config, Profile $profile)
    {
        $this->config = $config;
        $this->profile = $profile;

        $this->debugEnabled = $this->config->get('main', 'debug', false);
        $logfile = ltrim($this->config->get('logs', 'log_file'), "/\\");
        if($logfile == null) {
            $logfile = "logs/antibot.log";
        }
        $this->logFile = $config->BasePath . $logfile;

        $this->validateLogFile();
    }

    /**
     * Основной метод логирования (соответствует исходному logMessage)
     * @param string $message Сообщение для записи
     */
    public function logMessage($message)
    {
        if (!$this->debugEnabled) {
            return;
        }

        if (is_file($this->logFile) && !is_writable($this->logFile)) {
            error_log("The logfile is not writable: " . $this->logFile);
            return;
        }

        $logEntry = $this->formatLogEntry($message);
        $this->writeToFile($this->logFile, $logEntry);
    }

    /**
     * Альтернативный метод логирования с дополнительным контекстом
     * @param string $message Сообщение
     * @param array $context Дополнительные данные
     */
    public function log($message, $context = [])
    {
        $fullMessage = $message;
        if (!empty($context)) {
            $fullMessage .= " | " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        $this->logMessage($fullMessage);
    }

    /**
     * Форматирование записи лога
     * @param string $message
     * @return string
     */
    private function formatLogEntry($message)
    {
        return sprintf(
            "%s %s %s %s\n",
            date('Y-m-d H:i:s'),
            $this->profile->RayID,
            $this->profile->Ip,
            $message
        );
    }

    /**
     * Запись в файл с блокировкой
     * @param string $filePath
     * @param string $content
     */
    private function writeToFile($filePath, $content)
    {
        $fileHandle = fopen($filePath, 'a');
        if (!$fileHandle) {
            error_log("Failed to open log file: " . $filePath);
            return;
        }

        if (flock($fileHandle, LOCK_EX)) {
            fwrite($fileHandle, $content);
            flock($fileHandle, LOCK_UN);
        }

        fclose($fileHandle);
    }

    /**
     * Проверка доступности основного файла логов
     */
    private function validateLogFile()
    {
        if (file_exists($this->logFile) && !is_writable($this->logFile)) {
            throw new \RuntimeException("Main log file is not writable: " . $this->logFile);
        }

        $logDir = dirname($this->logFile);
        if (!is_dir($logDir) && !mkdir($logDir, 0755, true)) {
            throw new \RuntimeException("Failed to create log directory: " . $logDir);
        }
    }
}
