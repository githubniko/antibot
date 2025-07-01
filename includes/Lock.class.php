<?php

namespace WAFSystem;

/**
 * Класс для межпроцессной блокировки с использованием файловых блокировок
 * Поддерживает:
 * - Блокировку с ожиданием
 * - Неблокирующие попытки
 * - Проверку состояния блокировки
 * - Ожидание освобождения без захвата
 * - Автоочистку зависших блокировок
 */
class Lock
{
    /**
     * @var string Путь к файлу блокировки
     */
    private $lockFilePath;

    /**
     * @var resource|null Хэндл файла блокировки
     */
    private $lockHandle = null;

    /**
     * @var int Счетчик вложенных блокировок
     */
    private $lockCount = 0;

    /**
     * @var int PID текущего процесса
     */
    private $pid;

    /**
     * @var bool Использовать ли блокирующий режим
     */
    private $isBlocking;

    /**
     * @var int Максимальное время ожидания в секундах
     */
    private $maxWait;

    /**
     * @var int Время последней блокировки
     */
    private $lastLockTime = 0;

    /**
     * @var int Время жизни блокировки (сек) после которого считается зависшей
     */
    private $staleLockThreshold = 30;

    /**
     * Конструктор
     * 
     * @param string $lockFilePath Путь к файлу блокировки
     * @param bool $isBlocking Блокирующий режим (true - ждать, false - возвращать false при занятости)
     * @param int $maxWait Максимальное время ожидания в блокирующем режиме (секунд)
     */
    public function __construct($lockFilePath, $isBlocking = true, $maxWait = 5)
    {
        $this->lockFilePath = $lockFilePath;
        $this->pid = getmypid();
        $this->isBlocking = $isBlocking;
        $this->maxWait = $maxWait;

        // Создаем директорию для lock-файла если не существует
        $dir = dirname($this->lockFilePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Ожидает освобождения блокировки без захвата
     * 
     * @param int $timeout Максимальное время ожидания в секундах
     * @param int $checkInterval Интервал проверки в микросекундах (100000 = 100мс)
     * @return bool True если блокировка освобождена, false при таймауте
     */
    public function waitForUnlock($timeout = 5, $checkInterval = 100000)
    {
        $startTime = microtime(true);
        $timeoutMs = $timeout * 1000000;

        while ((microtime(true) - $startTime) * 1000000 < $timeoutMs) {
            if (!$this->isLockedByOtherProcess()) {
                return true;
            }
            usleep($checkInterval);
        }

        return false;
    }

    /**
     * Пытается получить блокировку
     * 
     * @return bool Успешность получения блокировки
     * @throws RuntimeException При ошибках файловой системы
     */
    public function Lock()
    {
        // Если уже владеем блокировкой - увеличиваем счетчик
        if ($this->lockCount > 0) {
            $this->lockCount++;
            return true;
        }

        $flags = LOCK_EX; // Эксклюзивная блокировка
        if (!$this->isBlocking) {
            $flags |= LOCK_NB; // Неблокирующий режим
        }

        $startTime = microtime(true);
        $timeout = $this->maxWait;

        do {
            // Открываем файл блокировки
            $this->lockHandle = fopen($this->lockFilePath, 'c+');
            if (!$this->lockHandle) {
                throw new \RuntimeException("Failed to open lock file: {$this->lockFilePath}");
            }

            // Пытаемся получить блокировку
            if (flock($this->lockHandle, $flags)) {
                // Записываем PID текущего процесса
                ftruncate($this->lockHandle, 0);
                fwrite($this->lockHandle, (string)$this->pid);
                fflush($this->lockHandle);

                $this->lockCount = 1;
                $this->lastLockTime = time();
                return true;
            }

            // Не удалось - закрываем хэндл
            fclose($this->lockHandle);
            $this->lockHandle = null;

            // В неблокирующем режиме проверяем таймаут
            if (!$this->isBlocking && (microtime(true) - $startTime) >= $timeout) {
                return false;
            }

            // Ждем перед повторной попыткой
            usleep(100000); // 100ms
        } while ($this->isBlocking || (microtime(true) - $startTime) < $timeout);

        return false;
    }

    /**
     * Освобождает блокировку
     */
    public function Unlock()
    {
        if ($this->lockCount <= 0) {
            return;
        }

        $this->lockCount--;

        // Если это последняя вложенная блокировка
        if ($this->lockCount === 0 && $this->lockHandle !== null) {
            // Очищаем файл
            ftruncate($this->lockHandle, 0);
            // Снимаем блокировку
            flock($this->lockHandle, LOCK_UN);
            // Закрываем файл
            fclose($this->lockHandle);
            $this->lockHandle = null;
        }
    }

    /**
     * Проверяет, есть ли у нас блокировка
     * 
     * @return bool
     */
    public function isOwnedByUs()
    {
        return $this->lockCount > 0;
    }

    /**
     * Проверяет, заблокирован ли ресурс другим процессом
     * 
     * @return bool
     */
    public function isLockedByOtherProcess()
    {
        // Если блокировка наша - значит не заблокировано другими
        if ($this->isOwnedByUs()) {
            return false;
        }

        // Если файла блокировки нет - ресурс свободен
        if (!file_exists($this->lockFilePath)) {
            return false;
        }

        // Проверяем время последней модификации
        $lockTime = filemtime($this->lockFilePath);
        $currentTime = time();

        // Если блокировка "зависла" (старше threshold) - очищаем
        if ($currentTime - $lockTime > $this->staleLockThreshold) {
            @unlink($this->lockFilePath);
            return false;
        }

        // Проверяем PID блокирующего процесса
        $lockingPid = @file_get_contents($this->lockFilePath);
        if ($lockingPid === false) {
            return false;
        }

        // Если процесс с этим PID еще существует - блокировка активна
        return $lockingPid != $this->pid && $this->isProcessActive($lockingPid);
    }

    /**
     * Проверяет активность процесса по PID
     * 
     * @param int $pid
     * @return bool
     */
    private function isProcessActive($pid)
    {
        if (empty($pid) || !is_numeric($pid)) {
            return false;
        }

        // Пробуем posix_kill(), если доступна
        if (function_exists('posix_kill')) {
            return posix_kill($pid, 0);
        }

        // Если posix_kill() недоступна, используем exec
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: проверяем через tasklist
            exec("tasklist /FI \"PID eq $pid\" 2>NUL", $output);
            return !empty($output) && strpos(implode('', $output), (string)$pid) !== false;
        } else {
            // Linux: проверяем через ps или kill -0
            exec("ps -p $pid 2>/dev/null", $output, $returnCode);
            return $returnCode === 0 && count($output) > 1;
        }
    }

    /**
     * Деструктор - гарантирует освобождение блокировки
     */
    public function __destruct()
    {
        while ($this->lockCount > 0) {
            $this->Unlock();
        }
    }
}
