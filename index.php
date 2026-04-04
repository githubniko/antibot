<?php
if (PHP_SAPI !== 'cli') { // не вкл. защиту для CRON и локального запуска php

    $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $file_name = pathinfo($request_uri, PATHINFO_FILENAME);

    if (
        $file_name != 'xhr' && $file_name != 'favicon'
        && !isset($isInclude)
    ) { // нужно для совместимости с подключением через .htaccess
        $isInclude = true; // блокирует повторное подключение (совместимость с подключением через .htaccess)

        include "includes/autoload.php";

        // Инициализация и запуск системы
        try {
            $antiBot = \WAFSystem\WAFSystem::getInstance(); // Инициализация системы защиты
            $pageCache = new \WAFSystem\PageCache($antiBot); // Инициализация модуля кеширования

            if ($antiBot->enabled) {
                $antiBot->IFrameChecker->HeaderBlock(); // блокировка отображения в IFrame

                if (isset($_GET['awafblock'])) // переменная блокировки через JS
                    $antiBot->Template->showBlockPage();

                $antiBot->run();
            }

            if ($pageCache->enabled) {
                echo $pageCache->Open(); // Загрузка страницы через модуль кеширования
            } else {
                include $_SERVER["DOCUMENT_ROOT"] . "/index.php.origin";
            }
        } catch (Exception $e) {
            error_log("AntiBot system failed: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            header("HTTP/1.1 500 Internal Server Error");
            exit;
        }
    }
}
