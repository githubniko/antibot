<?php

/**
 * Автоматически подключает все PHP-файлы в указанной директории и её поддиректориях
 * с кешированием уже загруженных файлов.
 * 
 * @param string $dir Начальная директория для поиска (по умолчанию - текущая)
 * @param array &$loadedFiles Массив для отслеживания загруженных файлов (внутреннее использование)
 */
function autoloadAllPhpFiles($dir = __DIR__, &$loadedFiles = [])
{
    if (!is_dir($dir)) {
        throw new InvalidArgumentException("Directory {$dir} does not exist");
    }

    $items = new FilesystemIterator($dir, FilesystemIterator::SKIP_DOTS);

    // Сначала собираем все файлы и директории, сортируем их
    $files = [];
    $subDirs = [];

    foreach ($items as $item) {
        $path = $item->getPathname();

        if ($item->isDir()) {
            $subDirs[] = $path;
            continue;
        }

        if (
            $item->isFile() &&
            $item->getExtension() === 'php' &&
            $item->getFilename() !== 'autoload.php' &&
            !isset($loadedFiles[$path])
        ) {
            $files[] = $path;
        }
    }

    // Сортируем файлы по алфавиту (чтобы, например, BaseClass.php загрузился раньше ChildClass.php)
    sort($files);

    // Загружаем файлы из текущей директории
    foreach ($files as $file) {
        require_once $file;
        $loadedFiles[$file] = true;
    }

    // Затем рекурсивно загружаем вложенные директории
    foreach ($subDirs as $subDir) {
        autoloadAllPhpFiles($subDir, $loadedFiles);
    }
}

$loadedFiles = [];
autoloadAllPhpFiles(__DIR__, $loadedFiles);
