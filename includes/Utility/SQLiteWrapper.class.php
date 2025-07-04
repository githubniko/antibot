<?php

namespace Utility;

class SQLiteWrapper
{
    private $db;
    private $dbPath;
    private $Logger;
    private $defaultRetryDelay = 100; // Начальная задержка в мс
    private $maxRetryDelay = 2000;    // Максимальная задержка в мс

    public function __construct($dbPath, \WAFSystem\Logger $logger)
    {
        if (!class_exists('SQLite3')) {
            $msg = 'SQLite3 PHP extension is not loaded';
            $this->Logger->log($msg, static::class);
            throw new \Exception($msg);
        }

        $this->dbPath = $dbPath;
        $this->Logger = $logger;
        $this->connect();
    }

    private function connect()
    {
        $bInit  = (!is_file($this->dbPath)) ? true : false;

        $this->db = new \SQLite3($this->dbPath);

        if ($bInit) {
            $this->db->exec('PRAGMA journal_mode = WAL');
            $this->db->exec('PRAGMA synchronous = NORMAL');
        }
        $this->db->exec('PRAGMA temp_store = MEMORY');
    }

    /**
     * Выполняет запрос с обработкой блокировок
     * 
     * @param string $query SQL-запрос
     * @param array $params Параметры для bind
     * @param int $maxAttempts Максимальное число попыток
     * @return SQLite3Result|false
     * @throws \RuntimeException
     */
    public function query($query, $params = [], $maxAttempts = 3)
    {
        $attempt = 0;
        $delay = $this->defaultRetryDelay;

        while ($attempt < $maxAttempts) {
            try {
                $stmt = @$this->db->prepare($query);

                // Критически важная проверка
                if ($stmt === false) {
                    $error = $this->db->lastErrorMsg();
                    if (strpos($error, 'database is locked') !== false) {
                        throw new \RuntimeException("Database is locked". $error);
                    }
                    throw new \RuntimeException("Prepare failed: " . $error);
                }

                foreach ($params as $name => $value) {
                    $stmt->bindValue($name, $value[0], $value[1]);
                }

                $result = @$stmt->execute();

                if ($result === false) {
                    throw new \RuntimeException("Execute failed");
                }

                return $result;
            } catch (\Exception $e) {
                $attempt++;

                if (
                    $attempt >= $maxAttempts ||
                    strpos($e->getMessage(), 'database is locked') === false
                ) {
                    throw $e;
                }

                $this->Logger->log("Database locked, attempt $attempt/$maxAttempts");
                usleep($delay * 1000);
                $delay = min($delay * 2, $this->maxRetryDelay);
            }
        }

        throw new \RuntimeException("Failed after $maxAttempts attempts");
    }

    /**
     * Выполняет запрос на модификацию данных
     * 
     * @param string $query SQL-запрос
     * @param int $maxAttempts Максимальное число попыток
     * @return bool
     * @throws \RuntimeException
     */
    public function exec($query, $maxAttempts = 3)
    {
        $attempt = 0;
        $delay = $this->defaultRetryDelay;

        while ($attempt < $maxAttempts) {
            try {
                $result = @$this->db->exec($query);
                if ($result !== false) {
                    return $result;
                }

                // Анализ ошибки, если exec вернул false
                $error = $this->db->lastErrorMsg();
                if (strpos($error, 'database is locked') !== false) {
                    throw new \RuntimeException("Database is locked". $error);
                }
                throw new \RuntimeException("Exec failed: " . $error);
            } catch (\Exception $e) {
                $attempt++;

                if (
                    $attempt >= $maxAttempts ||
                    strpos($e->getMessage(), 'database is locked') === false
                ) {
                    throw $e;
                }

                $this->Logger->log("Exec locked, attempt $attempt/$maxAttempts");
                usleep($delay * 1000);
                $delay = min($delay * 2, $this->maxRetryDelay);
            }
        }

        throw new \RuntimeException("Failed after $maxAttempts attempts");
    }

    public function __destruct()
    {
        if ($this->db instanceof \SQLite3) {
            $this->db->close();
        }
    }
}
