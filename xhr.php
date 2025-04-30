<?php
session_start();
header('Content-type: application/json; charset=utf-8');

include "includes/autoload.php";

// Инициализация и запуск системы
try {
    $antiBot = new \WAFSystem\WAFSystem();
    $antiBot->isAllowed2();
} catch (Exception $e) {
    error_log("AntiBot system failed: " . $e->getMessage());
    header("HTTP/1.1 500 Internal Server Error");
    exit;
}