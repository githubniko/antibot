<?php
session_start();
$RayID = ""; // ид для идентификации пользователя в лог-файле
$RayIDSecret = ""; // секретный ид для названия куки

# Функция для вывода страницы проверки и ввода каптчи
define(
	'DISPLAY_CAPTCHA_FORM_EXIT',
	'logMessage("Отображение страницы проверки");
    require "template.inc.php";
	exit;'
);

# Функция для вывода страницы блокировки
define(
	'DISPLAY_BLOCK_FORM_EXIT',
	'logMessage("Отображение страницы блокировки");
    require "template_block.inc.php";
	exit;'
);

function logMessage($message, $logFile = 'antibot.log')
{
	global $AB_DEBUGE, $DOCUMENT_ROOT, $HTTP_ANTIBOT_PATH;

	if (!$AB_DEBUGE) return;

	$logFile = basename($logFile); // Защита от атак через путь
	$logFilePath = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . $logFile;

	if (is_file($logFilePath) && !is_writable($logFilePath)) {
		error_log("The logfile is not writable.: " . $logFilePath);
		return;
	}

	// Формируем строку для записи в лог
	$logEntry = "" . date('Y-m-d H:i:s') . " " . getRayID() . " " . $_SERVER['REMOTE_ADDR'] . " " . $message . PHP_EOL;

	// Открываем файл для записи (если файл не существует, он будет создан)
	$fileHandle = fopen($logFilePath, 'a');

	// Записываем сообщение в файл
	if (flock($fileHandle, LOCK_EX)) {
		fwrite($fileHandle, $logEntry);
		flock($fileHandle, LOCK_UN);
	}

	// Закрываем файл
	fclose($fileHandle);
}

function endJSON($status)
{
	$res = array('status' => $status);

	# каждый раз генерируем ключ, чтобы форму не DDOS  
	if ($status == 'captcha') {
		$_SESSION['keyID'] = genKey();
		$res['keyID'] = $_SESSION['keyID'];
		logMessage("Показ каптчи");
	}

	echo json_encode($res);
	exit;
}

# генерирует случайный код
function genKey()
{
	return md5(rand(100, 200));
}

# генерирует идентификатор пользователя для идентификации в лог-файле
function getRayID()
{
	global $RayID;
	$RayID = $RayID == "" ? substr(md5($_SERVER['HTTP_HOST'] . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']), 0, 16) : $RayID;
	return $RayID;
}

# генерирует идентификатор пользователя для маркера
function getRayIDSecret()
{
	global $RayIDSecret;
	$RayIDSecret = $RayIDSecret == "" ? substr(md5($_SERVER['HTTP_HOST'] . $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']), 16) : $RayIDSecret;
	return $RayIDSecret;
}

function setMarker()
{
	global $AB_EXPIRED_COOKIE;
	setcookie(getRayIDSecret(), genKey(), time() + $AB_EXPIRED_COOKIE * 24 * 3600, "/");
	logMessage("Установлен маркер");
}

function isMarker()
{
	global $AB_EXPIRED_COOKIE;
	if (isset($_COOKIE[getRayIDSecret()])) {
		return true;
	}
	return false;
}

# Белый список ip адресов
function whitelistIP($client_ip)
{
	global $DOCUMENT_ROOT, $HTTP_ANTIBOT_PATH;

	# ip адрес сервера выполнения скрипта, нужен для сron и т.п.
	if ($client_ip == $_SERVER['SERVER_ADDR'])
		return true;

	$whilelistipFilePath = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . 'lists/whilelist';

	$file = fopen($whilelistipFilePath, 'r');
	if (!$file) return false;

	while (($line = fgets($file)) !== false) {
		$line = trim($line);
		if (empty($line)) continue;

		$isFind = preg_match('/([0-9a-z\.\:]+)\s*.*/i', $line, $match);
		if($isFind > 0) {
			$ip = $match[1];

			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === false) {
				continue;
			}
			
			if ($ip == $client_ip) {
				fclose($file);
				return true;
			}
		}
		
	}
	fclose($file);
	return false;
}

# Черный список ip адресов
function blacklistIP($client_ip)
{
	global $DOCUMENT_ROOT, $HTTP_ANTIBOT_PATH;

	# ip адрес сервера выполнения скрипта, нужен для сron и т.п.
	if ($client_ip == $_SERVER['SERVER_ADDR'])
		return true;

	$whilelistipFilePath = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . 'lists/blacklist';

	$file = fopen($whilelistipFilePath, 'r');
	if (!$file) return false;

	while (($line = fgets($file)) !== false) {
		$line = trim($line);
		if (empty($line)) continue;

		$isFind = preg_match('/([0-9a-z\.\:]+)\s*.*/i', $line, $match);
		if($isFind > 0) {
			$ip = $match[1];

			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === false) {
				continue;
			}
			
			if ($ip == $client_ip) {
				fclose($file);
				return true;
			}
		}
		
	}
	fclose($file);
	return false;
}

# Добавляет айпи-адрес в черный список
function addToBlacklist($client_ip, $comment) 
{
	global $DOCUMENT_ROOT, $HTTP_ANTIBOT_PATH;

	# ip адрес сервера выполнения скрипта, нужен для сron и т.п.
	if ($client_ip == $_SERVER['SERVER_ADDR'])
		return;

	if(blacklistIP($client_ip))
		return;

	$blackListFilePath = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . 'lists/blacklist';

	if (is_file($blackListFilePath) && !is_writable($blackListFilePath)) {
		error_log("The file is not writable.: " . $blackListFilePath);
		return;
	}

	// Формируем строку для записи
	$mess = $client_ip ." ". getRayID() . (!empty($comment) ? ' #'.$comment:'') . PHP_EOL;

	// Открываем файл для записи (если файл не существует, он будет создан)
	$fileHandle = fopen($blackListFilePath, 'a');

	// Записываем сообщение в файл
	if (flock($fileHandle, LOCK_EX)) {
		fwrite($fileHandle, $mess);
		flock($fileHandle, LOCK_UN);
	}

	// Закрываем файл
	fclose($fileHandle);
}

# Функция для проверки, является ли пользовательский агент исключением
function isExcludedBotLegal($userAgent)
{
	# Список исключений (поисковые боты Яндекса и Google)
	# !!! Не использовать для посторонних ботов, т.к. $userAgent можно подделать
	$excludedBots = ['Googlebot', 'YandexBot'];

	foreach ($excludedBots as $bot) {
		if (strpos($userAgent, $bot) !== false)
			return true;
	}
	return false;
}

# Фнкуция проверяет ip на индексирующего бота
function isIndexbot($client_ip)
{
	// Выполняем обратный DNS-запрос
	$hostname = gethostbyaddr($client_ip);
	logMessage($hostname);

	$regList = [
		'\.googlebot\.com$',
		'\.google\.com$',
		'\.yandex\.ru$',
		'\.yandex\.net$',
		'\.yandex\.com$',
		'\.msn\.com$', // http://www.bing.com/bingbot.htm
		'\.petalsearch\.com$', // для сервисов Huawei https://webmaster.petalsearch.com/site/petalbot
		'\.apple\.com$', // http://www.apple.com/go/applebot
		'\.baidu\.com$', // http://www.baidu.com/search/spider.html
		'\.baidu\.jp$', // http://www.baidu.com/search/spider.html
	];

	mb_regex_encoding('UTF-8');   //кодировка строки

	// Проверяем, заканчивается ли доменное имя на .googlebot.com или .google.com
	foreach ($regList as $reg) // перебираем регулярные выражения, пока не найдется хоть одно совпадение
	{
		$count = preg_match("/$reg/i", $hostname, $match);   //поиск подстрок в строке pValue
		if ($count > 0) {
			# тут можно выполняем прямой DNS-запрос для проверки
			# например : $resolvedIps = gethostbynamel($hostname);
			#if ($resolvedIps && in_array($client_ip, $resolvedIps)) {
			#	return true;
			#}
			return true;
		}
	}

	return false;
}

# Разрешающие фильтры
function isAllow()
{
	logMessage("-- Вход " . $_SERVER['HTTP_USER_AGENT']);

	# Проверка на установленную метку
	if (isMarker()) {
		logMessage("Найден маркер");
		return true;
	}

	# Проверка IP на белый лист
	if (whitelistIP($_SERVER['REMOTE_ADDR'])) {
		logMessage("Находится в белом списке");
		return true;
	}

	# Проверяем является ли пользователь индексирующим ботом Яндек, Гугл
	if (isIndexbot($_SERVER['REMOTE_ADDR'])) {
		logMessage("Индексирующий робот");
		### ТУТ МОЖНО СДЕЛАТЬ ДОБАВЛЕНИЕ АЙПИ В БЕЛЫЙ ЛИСТ ДЛЯ ОПТИМИЗАЦИИ СКОРОСТИ ОБРАБОТКИ
		return true;
	}

	if(blacklistIP($_SERVER['REMOTE_ADDR'])) {
		logMessage("IP-адрес найден в черном списке");
		eval(DISPLAY_BLOCK_FORM_EXIT);
	}

	# Проверка, является ли пользовательский агент исключением
	//if (isExcludedBotLegal($_SERVER['HTTP_USER_AGENT'])) {
	//	logMessage("UserAgent содержит фразу-исключение $bot");
	//	return true;
	//} 

	

	return false;
}
