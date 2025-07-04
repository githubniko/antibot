<?php

namespace WAFSystem;

/**
 * Класс содержит базовые методы работы с листами
 */
abstract class ListBase
{
    protected $absolutePath;
    protected $path; // путь от корня проекта
    protected $listName; // название листа в конфиг-файле
    protected $Config;
    protected $Logger;
    protected $Lock;
    public $enabled = true;

    public function __construct($pathFile, Config $config, Logger $logger)
    {
        $this->Config = $config;
        $this->Logger = $logger;
        $this->Lock = new Lock($this->Config->CachePath . 'lock/.' . $this->listName . '.lock');

        $this->absolutePath = $config->BasePath . $pathFile;
        $this->path = $pathFile;

        if (!$this->enabled) return; // выходим, если модуль выключен
        
        $this->Lock->waitForUnlock(); // ждем разрешение класса блокировки процесса
        
        $this->initListFile();
    }

    /**
     * Проверяет наличие значения в листе
     */
    public function isListed($value)
    {
        if (!$this->validate($value)) {
            $this->Logger->log("Error: The value '$value' failed validation", [static::class]);
            return false;
        }

        return $this->checkInList($value);
    }

    /**
     * Добавляет в конец списка
     */
    public function add($value, $comment = '')
    {
        $this->Lock->Lock();
        try {
            if (!$this->validate($value)) {
                $this->Logger->log("Error: The value '$value' failed validation", [static::class]);
                return;
            }


            if ($this->isListed($value)) {
                return;
            }

            $entry = $this->formatEntry($value, $comment);
            $this->saveEntry($entry);

            $this->Logger->logMessage("Value added to list: " . $value . " (" . $this->path . ")");
        } finally {
            $this->Lock->Unlock();
        }
    }

    /**
     * Читает список в массив
     */
    public function readToArray()
    {
        $arr = [];
        $file = fopen($this->absolutePath, 'r');
        if (!$file) {
            $this->Logger->logMessage("Error reading file: " . $this->absolutePath, [static::class]);
            return false;
        }

        try {
            while (($line = fgets($file)) !== false) {
                $lineValue = $this->extractFromLine($line);
                if (!empty($lineValue)) {
                    array_push($arr, $lineValue);
                }
            }
        } finally {
            fclose($file);
        }
        return $arr;
    }

    /**
     * Метод задает шаблон заполнения листа
     */
    protected function formatEntry($value, $comment)
    {
        return $value . (empty($comment) ? '' : " # " . date("Y-m-d H:i:s") . ' ' . trim($comment));
    }

    /**
     * Проверяет наличие записи в листе
     */
    protected function checkInList($value)
    {
        if (!is_file($this->absolutePath)) {
            $this->Logger->log("Critical error: file list not found, check parameters " . $this->listName, [static::class]);
            return false;
        }

        $file = fopen($this->absolutePath, 'r');
        if (!$file) {
            $this->Logger->logMessage("Error reading file: " . $this->absolutePath, [static::class]);
            return false;
        }

        try {
            while (($line = fgets($file)) !== false) {
                $lineValue = $this->extractFromLine($line);
                if (!empty($lineValue)) {
                    if ($this->Comparison($lineValue, $value)) {
                        $this->Logger->log("Value found in list: " . $lineValue . " (" . $this->path . ")", [static::class]);
                        return true;
                    }
                }
            }
        } finally {
            fclose($file);
        }

        return false;
    }

    /**
     * Обрабатывает сравнение значений. Нужно переопределить для сравнения IP или других нетиповых значений
     */
    protected function Comparison($value1, $value2)
    {
        if ($value1 === $value2)
            return true;
        return false;
    }
    /**
     * Инициализация, вызывается в конструкторе класса
     */
    protected function initListFile()
    {
        $this->Lock->Lock();
        try {
            if (!file_exists($this->absolutePath)) {
                $this->saveListFile();
                $this->Logger->logMessage("New list file created: " . $this->absolutePath);

                $this->eventInitListFile();
            }
        } finally {
            $this->Lock->Unlock();
        }
    }

    /**
     * Записывает данные в лист
     */
    protected function saveListFile()
    {
        $defaultContent = $this->createDefaultFileContent();
        file_put_contents($this->absolutePath, $defaultContent);
    }

    /**
     * Метод записи в конец листа
     */
    protected function saveEntry($value) {
    $file = fopen($this->absolutePath, 'a+'); // Открываем для чтения и записи
    if (flock($file, LOCK_EX)) {
        // Проверяем последний символ файла
        fseek($file, -1, SEEK_END);
        $fileSize = ftell($file);
        
        if ($fileSize > 0) { // Если файл не пустой
            $lastChar = fread($file, 1);
            if ($lastChar != "\n") {
                fwrite($file, PHP_EOL); // Добавляем перенос, если его нет
            }
        }
        
        fwrite($file, $value); // Пишем саму запись
        flock($file, LOCK_UN);
    }
    fclose($file);
}

    /**
     * Извлекает значение из строки. Удаляет комментарии 
     */
    protected function extractFromLine($line)
    {
        $line = trim(preg_replace('/#.*$/', '', $line));
        return $line;
    }

    protected function eventInitListFile() {} // Событие срабатывает после создании файла листа. Нужно, если требуется сделать первую запись в лист сразу после создания, например внести белые ip-адреса серверов
    protected function validate($value)
    {
        return true;
    } // Проверяет извеченное из листа значение

    abstract protected function createDefaultFileContent(); // Метод для первоначального заполнения листа, например примерами/шаблонами

}
