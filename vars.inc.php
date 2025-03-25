<?php
$AB_DEBUGE = TRUE;
$AB_SCREEN_WHIDTH = 1920; // px, минимальная ширина экрана
$AB_EXPIRED_COOKIE = 14; // дней, действия метки
$AB_IS_TOR = FALSE; // TRUE - блокировать вход с ip tor-сетей
$AB_IS_MOBILE = FALSE; // TRUE - каптча для мобильных девайсов
$AB_IS_IFRAME = FALSE; // TRUE - блокировать если открытие во if-frame
$AB_IS_DIRECT = FALSE; // TRUE - каптча для прямого захода
$AB_IS_IPV6 = FALSE; // TRUE - каптча для IPv6

$DOCUMENT_ROOT = rtrim( getenv("DOCUMENT_ROOT"), "/\\" );
$HTTP_HOST = getenv("HTTP_HOST");
$HTTP_ANTIBOT_PATH = "/antibot/";

?>