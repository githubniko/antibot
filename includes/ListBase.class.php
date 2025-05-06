<?php

namespace WAFSystem;

/**
 * Класс содержит базовые методы работы с листами
 */
abstract class ListBase
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
     * Проверяет наличие заначения в листе
     */
    public function isListed($value)
    {
        if (!$this->validate($value)) {
            $this->Logger->log("Error: The value $value failed validation", [static::class]);
            return false;
        }

        return $this->checkInList($value);
    }

    /**
     * Добавляет в конец списка
     */
    public function add($value, $comment = '')
    {
        if ($this->isListed($value)) {
            return;
        }

        $entry = $this->formatEntry($value, $comment);
        $this->saveEntry($entry);

        $this->Logger->logMessage("Value added to list: " . $value . " (" . static::class . ")");
    }

    /**
     * Метод задает шаблон заполнения листа
     */
    protected function formatEntry($value, $comment)
    {
        return $value . (empty($comment) ? '' : " # " . trim($comment)) . PHP_EOL;
    }

    /**
     * Проверяет наличие записи в листе
     */
    protected function checkInList($value)
    {
        if (!file_exists($this->listFile)) {
            $this->Logger->log("File not found: " . $this->listFile);
            return false;
        }

        $file = fopen($this->listFile, 'r');
        if (!$file) {
            $this->Logger->logMessage("Error reading file: " . $this->listFile);
            return false;
        }

        try {
            while (($line = fgets($file)) !== false) {
                $lineValue = $this->extractFromLine($line);
                if (!empty($lineValue)) {
                    if ($this->Comparison($lineValue, $value)) {
                        $this->Logger->log("Value found in list: ". $lineValue, [static::class] );
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
        if (!file_exists($this->listFile)) {
            $defaultContent = $this->createDefaultFileContent();
            file_put_contents($this->listFile, $defaultContent);
            $this->Logger->logMessage("New list file created: " . $this->listFile);

            $this->eventInitListFile();
        }
    }

    /**
     * Метод записи в конец листа
     */
    protected function saveEntry($value)
    {
        $file = fopen($this->listFile, 'a');
        if (flock($file, LOCK_EX)) {
            fwrite($file, $value);
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
    protected function validate($value) { return true; } // Проверяет извеченное из листа значение

    abstract protected function createDefaultFileContent(); // Метод для первоначального заполнения листа, например примерами/шаблонами
    
}
