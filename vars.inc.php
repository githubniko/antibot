<?php
$AB_DEBUGE = TRUE;
$AB_SCREEN_WHIDTH = 1920; // px, минимальная ширина экрана
$AB_EXPIRED_COOKIE = 14; // дней, действия метки
$AB_TOREXIT_BLOCK = FALSE; // TRUE - блокировать вход с ip tor-сетей

$DOCUMENT_ROOT = rtrim( getenv("DOCUMENT_ROOT"), "/\\" );
$HTTP_HOST = getenv("HTTP_HOST");
$HTTP_ANTIBOT_PATH = "/antibot/";

?>