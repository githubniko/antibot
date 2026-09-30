<?php

namespace WAFSystem;

/**
 * Обновление системы из репозитория
 */
class SysUpdate
{
    private $repoOwner = 'githubniko';
    private $repoName = 'antibot';
    private $branch = 'main'; // или 'master'
    private $enabled = false;
    private $lastUpdate = null;
    private $lastCommitDate = null;

    private $Config;
    private $Logger;

    public function __construct(Config $config, Logger $logger)
    {
        $this->Config = $config;
        $this->Logger = $logger;


        $this->enabled = $this->Config->init('sysupdate', 'enabled', 'Off', 'On - обновит систему при следующем запуске');
        $this->branch = $this->Config->init('sysupdate', 'branch', 'master', 'master - стабильный выпуск, dev - для тестировщиков');
        $this->lastUpdate = $this->Config->init('sysupdate', 'lastupdate', '', 'дата последнего обновления системы');

        if ($this->enabled === true) {
            $lock = new Lock($this->Config->BasePath . '.sysupgrade.lock');
            $lock->Lock();
            $this->Logger->log("Start upgrade", [get_class($this)]);
            if ($this->isUpdate()) {
                $this->Logger->log("Found new version", [get_class($this)]);

                if ($this->Update()) {
                    $this->Logger->log("Updated successfully", [get_class($this)]);
                    $this->Config->set('sysupdate', 'lastupdate', date('Y-m-d H:i:s'));
                } else {
                    $this->Logger->log("Update error, please try again", [get_class($this)]);
                }
            }
            $this->Config->set('sysupdate', 'enabled', 'Off');
            $this->Config->set('sysupdate', 'version', file_get_contents($this->Config->BasePath . 'VERSION'));
            $this->Logger->log("End upgrade", [get_class($this)]);
            $lock->Unlock();
        }
    }

    /**
     * Получаем дату последнего коммита через GitHub API
     */
    private function GetLastDateUpdate()
    {
        $apiUrl = "https://api.github.com/repos/{$this->repoOwner}/{$this->repoName}/commits/{$this->branch}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AntibotWAF_System');
        $response = curl_exec($ch);

        if (!$response) {
            $this->Logger->log("Не удалось получить данные из GitHub API.", [get_class($this)]);
            return null;
        }

        $commitData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($commitData['commit']['committer']['date'])) {
            $this->Logger->log($commitData['message'], [get_class($this)]);
            return null;
        }

        return strtotime($commitData['commit']['committer']['date']);
    }

    /**
     * Проверяет есть ли новая версия
     * true - есть, false - нет
     */
    private function isUpdate()
    {
        $this->lastCommitDate = $this->GetLastDateUpdate();
        if ($this->lastCommitDate == null) {
            return true;
        }
        if (!empty($this->lastUpdate) && $this->lastCommitDate <= strtotime($this->lastUpdate)) {
            return false;
        }
        return true;
    }

    public function Update()
    {
        $baseDir = substr($this->Config->BasePath, 0, -1);
        $zipUrl = "https://github.com/{$this->repoOwner}/{$this->repoName}/archive/refs/heads/{$this->branch}.zip";
        $zipFile = $baseDir . "/{$this->repoName}-{$this->branch}.zip";


        $curl = new \Utility\Curl();
        $zipContent = $curl->fetch(is_file($zipFile) ? $zipFile : $zipUrl);

        if ($zipContent === false) {
            $this->Logger->log("Не удалось скачать архив с GitHub.", [get_class($this)]);
            return false;
        }

        if (file_put_contents($zipFile, $zipContent) === false) {
            $this->Logger->log("Не удалось сохранить архив.", [get_class($this)]);
            return false;
        }

        if (!class_exists('ZipArchive')) {
            $this->Logger->log("Требуется расширение ZipArchive.", [get_class($this)]);
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== true) {
            $this->Logger->log("Не удалось открыть архив.", [get_class($this)]);
            return false;
        }

        // Удаляем старую версию (если есть)
        if (is_dir($baseDir)) {
            // Исключения
            $exclude = [
                '.git',
                '.gitignore',
                '.ignoreupdate',
                'lists',
                'logs',
                'cache',
                '*.ini',
                '*.bak',
                '*.backup',
                '*.origin',
                basename($zipFile) // архив
            ];
            $exclude = array_merge($exclude, $this->getIgnoreUpdateExclude($baseDir));
            $this->removeDirectory($baseDir, $exclude);
        }

        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0755, true);
        }

        // Извлекаем файлы (пропускаем корневую папку 'antibot-main')
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $filePath = $baseDir . '/' . str_replace("{$this->repoName}-{$this->branch}/", '', $filename);

            if (substr($filename, -1) === '/') {
                if (!is_dir($filePath)) {
                    mkdir($filePath, 0755, true);
                }
            } else {
                $stream = $zip->getStream($filename);
                try {
                    if ($stream !== false) {
                        file_put_contents($filePath, $stream);
                    }
                } catch (\Exception $e) {
                    if ($stream !== false) {
                        fclose($stream);
                    }
                    throw $e;
                }
                if ($stream !== false) {
                    fclose($stream);
                }
            }
        }

        $zip->close();
        unlink($zipFile);

        return true;
    }

    /**
     * Функция для удаления папки рекурсивно
     */
    private function getIgnoreUpdateExclude($baseDir)
    {
        $ignoreFile = $baseDir . '/.ignoreupdate';

        if (!is_file($ignoreFile) || !is_readable($ignoreFile)) {
            return [];
        }

        $lines = file($ignoreFile, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            $this->Logger->log("Не удалось прочитать .ignoreupdate.", [get_class($this)]);
            return [];
        }

        $exclude = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || strpos('#;', $line[0]) !== false || strpos($line, '//') === 0) {
                continue;
            }

            $line = str_replace('\\', '/', $line);
            $line = preg_replace('#/+#', '/', $line);
            $line = preg_replace('#^\./#', '', $line);
            $line = trim($line, '/');

            if ($line === '') {
                continue;
            }

            $exclude[] = $line;
        }

        return array_values(array_unique($exclude));
    }

    private function removeDirectory($dir, $exclude = [])
    {
        if (!is_dir($dir)) return;

        $rootDir = rtrim(str_replace('\\', '/', substr($this->Config->BasePath, 0, -1)), '/');
        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file == '.' || $file == '..') continue;

            $path = $dir . '/' . $file;
            $relativePath = ltrim(substr(str_replace('\\', '/', $path), strlen($rootDir)), '/');
            $shouldExclude = $this->isExcludedPath($file, $relativePath, $exclude);

            // Проверка на исключения (точное совпадение или по маске)
            foreach ($exclude as $pattern) {
                // Если это маска с звездочкой (например *.ini)
                if (strpos($pattern, '*') !== false) {
                    // Преобразуем маску в regex
                    $regex = '/^' . str_replace(['.', '*'], ['\.', '.*'], $pattern) . '$/';
                    if (preg_match($regex, $file)) {
                        $shouldExclude = true;
                        break;
                    }
                }
                // Точное совпадение имени
                elseif ($file == $pattern) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) continue;

            if (is_dir($path)) {
                $this->removeDirectory($path, $exclude);
            } else {
                unlink($path);
            }
        }

        if (realpath($dir) != realpath($this->Config->BasePath)) {
            rmdir($dir);
        }
    }

    private function isExcludedPath($file, $relativePath, $exclude)
    {
        foreach ($exclude as $pattern) {
            $pattern = trim(str_replace('\\', '/', $pattern), '/');

            if ($pattern === '') {
                continue;
            }

            if (strpos($pattern, '*') !== false) {
                $regex = '/^' . str_replace(['.', '*'], ['\.', '.*'], $pattern) . '$/';
                if (preg_match($regex, $file) || preg_match($regex, $relativePath)) {
                    return true;
                }
                continue;
            }

            if ($file == $pattern || $relativePath == $pattern || strpos($relativePath, $pattern . '/') === 0) {
                return true;
            }
        }

        return false;
    }
}
