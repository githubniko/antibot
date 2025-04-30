<?php
/**
 * Автоматически подключает все PHP-файлы в указанной директории и её поддиректориях
 * с кешированием уже загруженных файлов.
 * 
 * @param string $dir Начальная директория для поиска (по умолчанию - текущая)
 * @param array &$loadedFiles Массив для отслеживания загруженных файлов (внутреннее использование)
 */
function autoloadAllPhpFiles($dir = __DIR__, &$loadedFiles = []) {
    if (!is_dir($dir)) {
        throw new InvalidArgumentException("Directory {$dir} does not exist");
    }

    $items = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);
    
    foreach ($items as $item) {
        $path = $item->getPathname();

        if ($item->isDir()) {
            autoloadAllPhpFiles($path, $loadedFiles);
            continue;
        }
        
        if (
            $item->isFile() &&
            $item->getExtension() === 'php' &&
            $item->getFilename() !== 'autoload.php' &&
            !isset($loadedFiles[$path]) // Проверяем, не загружен ли файл уже
        ) {
            require_once $path;
            $loadedFiles[$path] = true; // Кешируем путь
        }
    }
}

$loadedFiles = [];
autoloadAllPhpFiles(__DIR__, $loadedFiles);