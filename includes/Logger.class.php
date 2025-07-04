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

        $this->config->init('main', 'debug', true);
        $this->config->init('logs', 'log_file', 'logs/antibot.log');
        $this->config->init('logs', 'max_size', 10, 'Максимальный размер, MB');
        $this->config->init('logs', 'rotate', 7, 'Количество файлов ротации');

        $this->debugEnabled = $this->config->get('main', 'debug', true);

        $logfile = ltrim($this->config->get('logs', 'log_file'), "/\\");
        if ($logfile == null) {
            $logfile = "logs/antibot.log";
            $this->config->set('logs', 'log_file', $logfile);
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

        $this->writeToFile($this->logFile, $this->formatLogEntry($message));
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
            $this->profile->IP,
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
        $retry = 0;
        $maxRetries = 3;

        while ($retry < $maxRetries) {
            if ($handle = @fopen($filePath, 'a')) {
                if (flock($handle, LOCK_EX)) {
                    fwrite($handle, $content);
                    flock($handle, LOCK_UN);
                    fclose($handle);
                    return;
                }
                fclose($handle);
            }
            $retry++;
            usleep(100000);
        }

        error_log("Failed to write log after {$maxRetries} attempts: " . $filePath);
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

    /**
     * Проверяет и выполняет ротацию логов при необходимости
     */
    public function rotateIfNeeded()
    {
        $maxSizeMB = $this->config->get('logs', 'max_size', 10);
        $rotateCount = $this->config->get('logs', 'rotate', 5);

        if (filesize($this->logFile) < ($maxSizeMB * 1024 * 1024)) {
            return;
        }

        $this->rotateLogs($rotateCount);
        $this->logMessage("Log rotation completed. Rotated files: {$rotateCount}");
    }

    private function rotateLogs($rotateCount)
    {
        // Переимнование лог-файла
        $tempFile = $this->logFile . '.temp_' . mt_rand();
        if (!rename($this->logFile, $tempFile)) {
            error_log("Failed to rename log file for rotation");
            return;
        }

        // Создаем новый файл
        touch($this->logFile);
        chmod($this->logFile, 0644);

        // Обновление архивов
        $this->processArchives($tempFile, $rotateCount);
    }

    /**
     * Архивирует и управляет архивными файлами
     */
    private function processArchives($sourceFile, $rotateCount)
    {
        // Создаем новый архив
        $newArchive = $this->compressFile($sourceFile);
        if (!$newArchive) {
            unlink($sourceFile);
            return;
        }

        // Удаляем самый старый архив
        $oldestArchive = $this->logFile . '.' . $rotateCount . '.gz';
        if (file_exists($oldestArchive)) {
            unlink($oldestArchive);
        }

        // Сдвигаем существующие архивы
        for ($i = $rotateCount - 1; $i >= 1; $i--) {
            $current = $this->logFile . '.' . $i . '.gz';
            $new = $this->logFile . '.' . ($i + 1) . '.gz';
            if (file_exists($current)) {
                rename($current, $new);
            }
        }

        // Перемещаем новый архив на место
        rename($newArchive, $this->logFile . '.1.gz');

        // Удаляем временный файл
        if (file_exists($sourceFile)) {
            unlink($sourceFile);
        }
    }

    /**
     * Архивирует файл в .gz формат
     * @param string $sourceFile Исходный файл
     * @return string|bool Путь к архиву или false при ошибке
     */
    private function compressFile($sourceFile)
    {
        if (!function_exists('gzopen')) {
            error_log("Zlib extension not available");
            return false;
        }

        $gzFile = $sourceFile . '.gz';
        $mode = 'wb9'; // Максимальное сжатие

        $fp_out = gzopen($gzFile, $mode);
        if (!$fp_out) {
            error_log("Cannot create gzip file: " . $gzFile);
            return false;
        }

        $fp_in = fopen($sourceFile, 'rb');
        if (!$fp_in) {
            gzclose($fp_out);
            unlink($gzFile);
            error_log("Cannot read source file: " . $sourceFile);
            return false;
        }

        while (!feof($fp_in)) {
            gzwrite($fp_out, fread($fp_in, 4096));
        }

        fclose($fp_in);
        gzclose($fp_out);

        return $gzFile;
    }
}
