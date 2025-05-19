<?php
namespace WAFSystem;

class Lock
{
    private $lockFile;

    public function __construct($lockFile)
    {
        $this->lockFile = $lockFile;
    }

    public function Lock()
    {
        $maxWait = 5;
        $startTime = time();
        $lock = null;

        while (time() - $startTime < $maxWait) {
            $lock = fopen($this->lockFile, 'w+');
            if (flock($lock, LOCK_EX | LOCK_NB)) {
                return $lock;
            }

            if (is_resource($lock)) {
                fclose($lock);
            }

            usleep(100000);
        }

        throw new \RuntimeException("Could not acquire config file lock after $maxWait seconds");
    }

    public function Unlock()
    {
        if (is_resource($this->lockFile)) {
            flock($this->lockFile, LOCK_UN);
            fclose($this->lockFile);
        }
        @unlink($this->lockFile);
    }
}