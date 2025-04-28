<?php
$AB_DEBUGE = TRUE;
$AB_SCREEN_WIDTH = 1920; // px, минимальная ширина экрана. Работает совместно с $AB_IS_MOBILE
$AB_EXPIRED_COOKIE = 14; // дней, действия метки
$AB_IS_TOR = TRUE; // TRUE - блокировать вход с ip tor-сетей
$AB_IS_MOBILE = TRUE; // TRUE - капча для мобильных девайсов
$AB_IS_IFRAME = TRUE; // TRUE - блокировать если открытие во if-frame
$AB_IS_DIRECT = TRUE; // TRUE - капча для прямого захода
$AB_IS_IPV6 = TRUE; // TRUE - капча для IPv6
$AB_IS_USERAGENT = TRUE; // TRUE - блокировка, если User-Agent отсутствуте или пустой
$AB_IS_404 = FALSE; 

$HTTP_ANTIBOT_PATH = "/antibot/";
$COOKIE_VALUE = "8d5e957f297893487bd98fa830fa6413"; // Значение метки. При изменении происходит сброс уже установленных меток. 
?>