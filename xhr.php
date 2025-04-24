<?php
@include "vars.inc.php";
include "includes/function.inc.php";
isVarFile();
session_start();
header('Content-type: application/json; charset=utf-8');


# status [
#	allow - прошел проверку
#	block - хакер, попытка обойти капчу
#	capcha - проверить капчей
#]

# Важен приоритет проверки

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	logMessage("Не POST-запрос");
	endJSON('block');
}
$data = json_decode(file_get_contents('php://input'), true);
//echo $data['referer'];exit;

if(!isset($data['func'])) {
	logMessage("Значение _data[func] не установлено");
	endJSON('block');
}

#### СДЕЛАТЬ БЛОКИРОВКУ ПО Raid_ID черному списку

# Запрос по событию Закрыл страницу или вкладку
if($data['func'] == 'win-close') {
	logMessage("Закрыл страницу проверки");
	endJSON(''); // возможно тут нужно добавлять пользователя в черный список
}

# Запрос на установку метки
if($data['func'] == 'set-marker') {
	if(empty($_SESSION['keyID'])) {
		logMessage("Значение _SESSION[keyID] не установлено");
		endJSON('block');
	}

	if(!isset($data['keyID']) || empty($data['keyID'])) {
		logMessage("Значение _data[keyID] не установлено");
		endJSON('block');
	}

	if($data['keyID'] != $_SESSION['keyID']) {
		logMessage("KeyID '$data[keyID]' не соответствует '$_SESSION[keyID]'");
		unset($_SESSION['keyID']);
		endJSON('block');
	}
	logMessage("Успешно прошел капчу");
	setMarker();
	unset($_SESSION['keyID']);
	endJSON('allow');
}

if ($AB_IS_IPV6 && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
	logMessage("Тип ip-адреса IPv6");
	endJSON('captcha');
}

# Проверка для мобильных девайсов
if($AB_IS_MOBILE && $data['screenWidth'] < $AB_SCREEN_WIDTH) {
	logMessage("Разрешение экрана меньше {$AB_SCREEN_WIDTH}px");
	endJSON('captcha');
}

if ($AB_IS_IFRAME && $data['mainFrame'] != true) {
	logMessage("Открытие во фрейме");
	addToBlacklist($_SERVER['REMOTE_ADDR'], 'iframe');
	endJSON('block');
}

if ($AB_IS_DIRECT && (empty($data['referer']) || mb_eregi("^http(s*):\/\/".$_SERVER['HTTP_HOST'] , $data['referer']))) {
	logMessage("Прямой заход");
	endJSON('captcha');
}

logMessage("Прошел все фильтры");
setMarker();


endJSON('allow');

?>