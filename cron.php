<?php
@ignore_user_abort(true);
@set_time_limit(0);
ini_set('max_execution_time', 600);

$LIMIT_TIME = 4320; // срок хранения DNS-кэша: 3 суток в минутах

if (PHP_SAPI !== 'cli') { die("Error: The script was not run in CLI mode."); }


$scriptPath = realpath(__FILE__);
if ($scriptPath === false) {
    fwrite(STDERR, "Не удалось определить путь к скрипту\n");
    exit(1);
}

$cahceDir = '/cache/';
$baseDir = dirname($scriptPath). $cahceDir;

// Не допускаем перенаправление всего дерева кэша через симлинк.
if (is_link(rtrim($baseDir, '/'))) {
    fwrite(STDERR, "Ошибка: каталог cache является символической ссылкой.\n");
    exit(1);
}

// Массив с именами целевых папок
$targetDirs = array('dns' => $LIMIT_TIME);
// Читаем конфигурацию без инициализации WAF и без изменения config.ini.
$configFile = dirname($scriptPath) . '/config.ini';
$sessionMinutes = null;
$configError = null;
if (!is_file($configFile) || !is_readable($configFile)) {
    $configError = "Конфигурация недоступна; очистка сессий отменена: $configFile";
} else {
    require_once dirname($scriptPath) . '/includes/Lock.class.php';
    require_once dirname($scriptPath) . '/includes/Utility/Domain.class.php';
    require_once dirname($scriptPath) . '/includes/Config.class.php';
    // Config использует SERVER_NAME, которого обычно нет в CLI.
    $hadServerName = isset($_SERVER['SERVER_NAME']);
    if (!$hadServerName) $_SERVER['SERVER_NAME'] = '';
    set_error_handler('cleanupConfigError');
    try {
        $config = \WAFSystem\Config::getInstance(dirname($scriptPath), '/', 'config.ini');
        // Только get(): не добавляем параметры и не перезаписываем файл.
        $days = $config->get('cookie', 'expire_days', 30);
        if (!is_scalar($days) || !preg_match('/^[0-9]+$/', (string)$days)
            || (float)$days <= 0 || (float)$days > PHP_INT_MAX / 86400) {
            $configError = 'Некорректное cookie.expire_days; очистка сессий отменена.';
        } else {
            $sessionMinutes = (int)$days * 1440;
            $targetDirs['sessid'] = $sessionMinutes;
        }
    } catch (\Exception $e) {
        $configError = 'Не удалось прочитать конфигурацию; очистка сессий отменена: ' . $e->getMessage();
    } finally {
        restore_error_handler();
        if (!$hadServerName) unset($_SERVER['SERVER_NAME']);
    }
}

$stats = array('files' => 0, 'directories' => 0, 'skipped' => 0, 'errors' => 0);
if ($configError !== null) cleanupError($configError, $stats);
foreach ($targetDirs as $dirName => $minutes) {
    $fullPath = $baseDir . $dirName;
    if (is_link($fullPath)) {
        $stats['skipped']++;
        continue;
    }
    if (!is_dir($fullPath)) {
        cleanupError("Каталог не найден: $fullPath", $stats);
        continue;
    }
    deleteOldFiles($fullPath, $minutes, $stats);
}
echo sprintf("Удалено файлов: %d; каталогов: %d; пропущено: %d; ошибок: %d\n",
    $stats['files'], $stats['directories'], $stats['skipped'], $stats['errors']);
exit($stats['errors'] > 0 ? 1 : 0);

/** Ошибки чтения Config не должны приводить к очистке с неверным сроком. */
function cleanupConfigError($severity, $message, $file, $line)
{
    if (!(error_reporting() & $severity)) return false;
    throw new \ErrorException($message, 0, $severity, $file, $line);
}

function cleanupError($message, &$stats)
{
    $stats['errors']++;
    fwrite(STDERR, "Ошибка: $message\n");
}

/** Удаляет старые файлы и опустевшие подкаталоги, не следуя симлинкам. */
function deleteOldFiles($directory, $minutes, &$stats)
{
    if (is_link($directory)) {
        $stats['skipped']++;
        return;
    }
    $cutoffTime = time() - ($minutes * 60);
    $items = @scandir($directory);
    if ($items === false) {
        cleanupError("Не удалось прочитать каталог: $directory", $stats);
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $filePath = $directory . '/' . $item;
        clearstatcache(true, $filePath);
        if (is_link($filePath)) {
            $stats['skipped']++;
            continue;
        }
        if (is_file($filePath)) {
            $mtime = @filemtime($filePath);
            if ($mtime === false) {
                cleanupError("Не удалось определить возраст файла: $filePath", $stats);
            } elseif ($mtime < $cutoffTime) {
                if (@unlink($filePath)) {
                    $stats['files']++;
                } else {
                    cleanupError("Не удалось удалить файл: $filePath", $stats);
                }
            } else {
                $stats['skipped']++;
            }
        } elseif (is_dir($filePath)) {
            deleteOldFiles($filePath, $minutes, $stats);
            clearstatcache(true, $filePath);
            if (is_link($filePath)) {
                $stats['skipped']++;
                continue;
            }
            $remaining = @scandir($filePath);
            if ($remaining === false) {
                cleanupError("Не удалось повторно прочитать каталог: $filePath", $stats);
            } elseif (count($remaining) === 2) {
                if (@rmdir($filePath)) {
                    $stats['directories']++;
                } else {
                    cleanupError("Не удалось удалить пустой каталог: $filePath", $stats);
                }
            }
        } else {
            cleanupError("Недоступный или неподдерживаемый объект: $filePath", $stats);
        }
    }
}
