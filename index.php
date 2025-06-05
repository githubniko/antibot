<?php

$reuest_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
if (basename($reuest_uri) != 'xhr.php') { // нужно для совместимости с подключением через .htaccess

    include "includes/autoload.php";

    // Инициализация и запуск системы
    try {
        $antiBot = new \WAFSystem\WAFSystem();

        if (isset($_GET['awafblock'])) // url блокировки через JS
            $antiBot->Template->showBlockPage();

        $antiBot->run();
    } catch (Exception $e) {
        error_log("AntiBot system failed: " . $e->getMessage());
        header("HTTP/1.1 500 Internal Server Error");
        exit;
    }
}
