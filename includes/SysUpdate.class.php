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
    private $Lock;

    public function __construct(Config $config, Logger $logger)
    {
        $this->Config = $config;
        $this->Logger = $logger;
        $this->Lock = new Lock($this->Config->BasePath . '.sysupgrade.lock');

        $this->enabled = $this->Config->init('sysupdate', 'enabled', 'Off', 'On - обновит систему при следующем запуске');
        $this->branch = $this->Config->init('sysupdate', 'branch', 'master', 'master - стабильный выпуск, dev - для тестировщиков');
        $this->lastUpdate = $this->Config->init('sysupdate', 'lastupdate', '', 'дата последнего обновления системы');

        if ($this->enabled) {
            $this->Lock->Lock();
            $this->Logger->log("Start upgrade", [static::class]);
            if ($this->isUpdate()) {
                $this->Logger->log("Found new version", [static::class]);

                if ($this->Update()) {
                    $this->Logger->log("Updated successfully", [static::class]);
                    $this->Config->set('sysupdate', 'lastupdate', date('Y-m-d H:i:s'));
                } else {
                    $this->Logger->log("Update error, please try again", [static::class]);
                }
            }
            $this->Config->set('sysupdate', 'enabled', 'Off');
            $this->Logger->log("End upgrade", [static::class]);
            $this->Lock->Unlock();
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
        curl_close($ch);

        if (!$response) {
            $this->Logger->log("Не удалось получить данные из GitHub API.", [static::class]);
            return null;
        }

        $commitData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($commitData['commit']['committer']['date'])) {
            $this->Logger->log($commitData['message'], [static::class]);
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
        if($this->lastCommitDate == null) {
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


        $zipContent = file_get_contents(is_file($zipFile) ? $zipFile : $zipUrl);
        if ($zipContent === false) {
            $this->Logger->log("Не удалось скачать архив с GitHub.", [static::class]);
            return false;
        }

        if (file_put_contents($zipFile, $zipContent) === false) {
            $this->Logger->log("Не удалось сохранить архив.", [static::class]);
            return false;
        }

        if (!class_exists('ZipArchive')) {
            $this->Logger->log("Требуется расширение ZipArchive.", [static::class]);
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipFile) !== true) {
            $this->Logger->log("Не удалось открыть архив.", [static::class]);
            return false;
        }

        // Удаляем старую версию (если есть)
        if (is_dir($baseDir)) {
            $exclude = [
                '.',
                '..',
                '.git',
                '.gitignore',
                'lists',
                'logs',
                $this->Config->configFileName,
                basename($zipFile)
            ];
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
                } finally {
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
    private function removeDirectory($dir, $exclude = [])
    {
        if (!is_dir($dir)) return;

        $files = array_diff(
            scandir($dir), // исключения
            $exclude
        );
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path, $exclude);
            } else {
                unlink($path);
            }
        }

        if ($dir != dirname($this->Config->BasePath)) {
            rmdir($dir);
        }
    }
}
