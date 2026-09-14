<?php
@ignore_user_abort(true);
@set_time_limit(0);
ini_set('max_execution_time', 600);

$LIMIT_TIME = 4320; // время жизни кэша в минутах

if (PHP_SAPI !== 'cli') { die("Error: The script was not run in CLI mode."); }


$scriptPath = realpath(__FILE__);
if ($scriptPath === false)
    die('Не удалось определить путь к скрипту');

$cahceDir = '/cache/';
$baseDir = dirname($scriptPath). $cahceDir;

// Массив с именами целевых папок
$targetDirs = array(
    'dns',
    'sessid'
);

foreach ($targetDirs as $dirName) {
    $fullPath = $baseDir . $dirName;
    
    // Проверяем, существует ли директория
    if (is_dir($fullPath)) {
        // Удаляем файлы старше 24 часов (1440 минут)
        deleteOldFiles($fullPath, $LIMIT_TIME);
        echo "Обработана директория: $fullPath\n";
    } else {
        echo "Предупреждение: Директория $fullPath не найдена.\n";
    }
}

/**
 * Рекурсивно удаляет файлы старше указанного количества минут
 * 
 * @param string $directory Путь к директории
 * @param int $minutes Количество минут
 */
function deleteOldFiles($directory, $minutes)
{
    $cutoffTime = time() - ($minutes * 60);
    
    // Открываем директорию
    $items = scandir($directory);
    
    foreach ($items as $item) {
        // Пропускаем текущую и родительскую директории
        if ($item === '.' || $item === '..') {
            continue;
        }
        
        $filePath = $directory . '/' . $item;
        
        // Если это файл и он старше заданного времени
        if (is_file($filePath) && filemtime($filePath) < $cutoffTime) {
            unlink($filePath);
        }
        // Если это директория - рекурсивно обрабатываем
        elseif (is_dir($filePath)) {
            deleteOldFiles($filePath, $minutes);
            // Удаляем пустую директорию (только если . и .. остались)
            $remaining = scandir($filePath);
            if (count($remaining) === 2) {
                rmdir($filePath);
            }
        }
    }
}