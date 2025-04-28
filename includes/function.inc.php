<?php
$DOCUMENT_ROOT = rtrim( getenv("DOCUMENT_ROOT"), "/\\" );
$HTTP_HOST = getenv("HTTP_HOST");
$HTTP_USER_AGENT = isset($_SERVER['HTTP_USER_AGENT']) ? htmlspecialchars($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8') : '';

require_once "tor.inc.php";

$RayID = ""; // ид для идентификации пользователя в лог-файле
$RayIDSecret = ""; // секретный ид для названия куки

# Функция для вывода страницы проверки и ввода капчи
function DISPLAY_CAPTCHA_FORM_EXIT() {
	global $DOCUMENT_ROOT, $HTTP_ANTIBOT_PATH;
	logMessage("Отображение страницы проверки");
    require $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . "templates/template.inc.php";
	exit;
}

# Функция для вывода страницы блокировки
function DISPLAY_BLOCK_FORM_EXIT() {
	global $DOCUMENT_ROOT, $HTTP_ANTIBOT_PATH;
	logMessage("Отображение страницы блокировки");
    require $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . "templates/template_block.inc.php";
	exit;
}

# Функция проверяет наличие конфиг. файла и создает его в случае отсутствия
function isVarFile()
{
	$DOCUMENT_ROOT = rtrim(getenv("DOCUMENT_ROOT"), "/\\");

	if (!is_file(__DIR__ . "/../vars.inc.php")) {
		if (!is_file(__DIR__ . "/../vars.inc.php.exemple")) {
			die("Error: Not file vars.inc.php.exemple");
		}
		if (!copy(__DIR__ . "/../vars.inc.php.exemple", __DIR__ . "/../vars.inc.php")) {
			die("Error: Failed to copy vars.inc.php");
		}
		ob_clean();
		header("Refresh:0");
		exit;
	}
}

# Выполняет необходимые действия при первом запуске
function initSystem()
{
	global $DOCUMENT_ROOT, $HTTP_ANTIBOT_PATH;

	# Создаем файлы правил и исключений
	$fileName = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . "lists/wl_request_url.rules";
	$data="# Список исключений по REQUEST_URI
# Можно писать регулярные выражения. Обрабатывается php функцией mb_eregi(). Правила проверяются поочередно до первого срабатывания.
# Символ #(решетка) используется как комментарий.";

	if(!is_file($fileName)) {
		if(!file_put_contents($fileName, $data)) {
			logMessage("Ошибка создания файла $fileName");
			return false;
		}
		logMessage("Создан файл $fileName");
		return true; 
	}

	$fileName = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . "lists/useragent.rules";
	$data="# Список разрешающих совпадений User-agent
# Можно писать регулярные выражения. Обрабатывается php функцией mb_eregi(). Правила проверяются поочередно до первого срабатывания.
# Символ # используется как комментарий.
# Примеры:
# Myboot 
# или Myb..t
# WhatsApp/[0-9.]+
# WhatsAppBot/[0-9.]+";

	if(!is_file($fileName)) {
		if(!file_put_contents($fileName, $data)) {
			logMessage("Ошибка создания файла $fileName");
			return false;
		}
		logMessage("Создан файл $fileName");
		return true; 
	}

	# добавляем в исключения ip серверов сайта
	$fileName = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . "list/whitelist";
	if(!is_file($fileName)) {
		$resolvedRecords = dns_get_record($_SERVER["HTTP_HOST"], DNS_ANY);

			// Проверяем, совпадает ли исходный IP с одним из разрешенных
			if (!empty($resolvedRecords)) {
				foreach ($resolvedRecords as $record) {
					if($record['type'] == 'A' || $record['type'] == 'AAAA') {
						addToWhitelist($record['type'] == 'AAAA' ? $record['ipv6'] : $record['ip'], $_SERVER["HTTP_HOST"]);
					}
				}
			}
	}
}



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
	if(!session_id()) {
		logMessage("Критическая ошибка: Сессия session_start() не начата.");
		$res = "Критическая ошибка: Сессия session_start() не начата.";
		echo json_encode($res);
		exit;
	}
	# каждый раз генерируем ключ, чтобы форму не DDOS  
	if ($status == 'captcha') {
		$_SESSION['keyID'] = genKey();
		$res['keyID'] = $_SESSION['keyID'];
		logMessage("Показ капчи");
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
	global $RayID, $HTTP_USER_AGENT;
	$RayID = $RayID == "" ? substr(md5($_SERVER['HTTP_HOST'] . $_SERVER['REMOTE_ADDR'] . $HTTP_USER_AGENT), 0, 16) : $RayID;
	return $RayID;
}

# генерирует идентификатор пользователя для маркера
function getRayIDSecret()
{
	global $RayIDSecret, $HTTP_USER_AGENT;
	$RayIDSecret = $RayIDSecret == "" ? substr(md5($_SERVER['HTTP_HOST'] . $_SERVER['REMOTE_ADDR'] . $HTTP_USER_AGENT), 16) : $RayIDSecret;
	return $RayIDSecret;
}

function setMarker()
{
	global $AB_EXPIRED_COOKIE;
	
	if (version_compare(PHP_VERSION, '7.3.0') >= 0) {
		setcookie(getRayIDSecret(), genKey(), [
			'expires' => time() + $AB_EXPIRED_COOKIE * 86400,
			'path' => '/',
			'httponly' => true,
			'secure' => isset($_SERVER['HTTPS'])
		]);
	} else {
		setcookie(getRayIDSecret(), genKey(), time() + $AB_EXPIRED_COOKIE * 24 * 3600, "/");
	}
	

	
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

	$filePath = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . 'lists/whitelist';

	if (!is_file($filePath))
		return false;

	$file = fopen($filePath, 'r');
	if (!$file) return false;

	while (($line = fgets($file)) !== false) {
		$line = trim($line);
		if (empty($line)) continue;

		$isFind = preg_match('/([0-9a-z\.\:]+)\s*.*/i', $line, $match);
		if ($isFind > 0) {
			$ip = $match[1];

			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === false) {
				continue;
			}

			if (inet_pton($ip) == inet_pton($client_ip)) {
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

	$filePath = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . 'lists/blacklist';

	if (!is_file($filePath))
		return false;

	$file = fopen($filePath, 'r');
	if (!$file) return false;

	while (($line = fgets($file)) !== false) {
		$line = trim($line);
		if (empty($line)) continue;

		$isFind = preg_match('/([0-9a-z\.\:]+)\s*.*/i', $line, $match);
		if ($isFind > 0) {
			$ip = $match[1];

			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) === false) {
				continue;
			}

			if (inet_pton($ip) == inet_pton($client_ip)) {
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

	if (blacklistIP($client_ip))
		return;

	$blackListFilePath = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . 'lists/blacklist';

	if (is_file($blackListFilePath) && !is_writable($blackListFilePath)) {
		error_log("The file is not writable.: " . $blackListFilePath);
		return;
	}

	// Формируем строку для записи
	$mess = $client_ip . " # " . getRayID() . (!empty($comment) ? ' ' . $comment : '') . PHP_EOL;

	// Открываем файл для записи (если файл не существует, он будет создан)
	$fileHandle = fopen($blackListFilePath, 'a');

	// Записываем сообщение в файл
	if (flock($fileHandle, LOCK_EX)) {
		fwrite($fileHandle, $mess);
		flock($fileHandle, LOCK_UN);
	}

	// Закрываем файл
	fclose($fileHandle);
	logMessage("IP-адрес добавлен в blacklist");
}

# Добавляет айпи-адрес в белый список
function addToWhitelist($client_ip, $comment)
{
	global $DOCUMENT_ROOT, $HTTP_ANTIBOT_PATH;

	# ip адрес сервера выполнения скрипта, нужен для сron и т.п.
	if ($client_ip == $_SERVER['SERVER_ADDR'])
		return;

	if (whitelistIP($client_ip))
		return;

	$filePath = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . 'lists/whitelist';

	if (is_file($filePath) && !is_writable($filePath)) {
		error_log("The file is not writable.: " . $filePath);
		return;
	}

	// Формируем строку для записи
	$mess = $client_ip . (!empty($comment) ? ' # ' . $comment : '') . PHP_EOL;

	// Открываем файл для записи (если файл не существует, он будет создан)
	$fileHandle = fopen($filePath, 'a');

	// Записываем сообщение в файл
	if (flock($fileHandle, LOCK_EX)) {
		fwrite($fileHandle, $mess);
		flock($fileHandle, LOCK_UN);
	}

	// Закрываем файл
	fclose($fileHandle);

	logMessage("IP-адрес добавлен в whitelist");
}

# Функция для проверки, является ли пользовательский агент исключением
function isExcludedBotLegal($userAgent)
{
	global $DOCUMENT_ROOT, $HTTP_ANTIBOT_PATH;

	$rulesPath = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . 'lists/useragent.rules';

	if (!is_file($rulesPath))
		return false;

	$file = fopen($rulesPath, 'r');
	if (!$file) return false;

	while (($line = fgets($file)) !== false) {
		$line = trim($line);
		if (empty($line)) continue;

		$strSearch = trim(mb_eregi('(.*)(#.*)', $line, $match) ? $match[1] : $line);
		if (empty($strSearch)) continue;
		if (mb_eregi($strSearch, $userAgent)) {
			logMessage("UserAgent содержит фразу-исключение: " . $strSearch);
			fclose($file);
			return true;
		}
	}
	fclose($file);
	return false;
}

# Функция поиска исключений по REQUEST_URL
function isWhiteListReauestUrl()
{
	global $DOCUMENT_ROOT, $HTTP_ANTIBOT_PATH;

	$rulesPath = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . 'lists/wl_request_url.rules';

	if (!is_file($rulesPath))
		return false;

	$file = fopen($rulesPath, 'r');
	if (!$file) return false;

	while (($line = fgets($file)) !== false) {
		$line = trim($line);
		if (empty($line)) continue;

		$strSearch = trim(mb_eregi('(.*)(#.*)', $line, $match) ? $match[1] : $line);
		if (empty($strSearch)) continue;
		if (mb_eregi($strSearch, $_SERVER['REQUEST_URI'])) {
			logMessage("REQUEST_URI содержит исключение: " . $strSearch);
			fclose($file);
			return true;
		}
	}
	fclose($file);
	return false;
}

# Фнкуция проверяет ip на индексирующего бота
function isIndexbot($client_ip)
{
	$isIPv6 = filter_var($client_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

	// Выполняем обратный DNS-запрос
	$hostname = gethostbyaddr($client_ip);
	logMessage('PTR: ' . $hostname);

	global $DOCUMENT_ROOT, $HTTP_ANTIBOT_PATH;

	$rulesPath = $DOCUMENT_ROOT . $HTTP_ANTIBOT_PATH . 'lists/indexbot.rules';

	if (!is_file($rulesPath))
		return false;

	$file = fopen($rulesPath, 'r');
	if (!$file) return false;

	while (($line = fgets($file)) !== false) {
		$line = trim($line);
		if (empty($line)) continue;

		$reg = trim(mb_eregi('(.*)(#.*)', $line, $match) ? $match[1] : $line);
		if (empty($reg)) continue;

		if (!validateDomain($reg)) {
			logMessage('Домен не валидный: ' . $reg);
			continue;
		}
		$reg = str_replace('.', '\.', $reg);

		mb_regex_encoding('UTF-8');   //кодировка строки

		// Проверяем, заканчивается ли доменное имя на .googlebot.com или .google.com
		$count = preg_match("/\.$reg$/i", $hostname, $match);   //поиск подстрок в строке pValue
		if ($count > 0) {
			// Выполняем прямой DNS-запрос в зависимости от типа IP
			$resolvedRecords = dns_get_record($hostname, $isIPv6 ? DNS_AAAA : DNS_A);

			// Проверяем, совпадает ли исходный IP с одним из разрешенных
			if (!empty($resolvedRecords)) {
				foreach ($resolvedRecords as $record) {
					if ($isIPv6) {
						if (isset($record['ipv6']) && $record['ipv6'] === $client_ip) {
							fclose($file);
							return true;
						}
					} else {
						if (isset($record['ip']) && $record['ip'] === $client_ip) {
							fclose($file);
							return true;
						}
					}
				}
			}
			fclose($file);
			return false;
		}
	}

	fclose($file);
	return false;
}

# Функция проверяет сущестование User-Agent
function isUserAgent()
{
	global $HTTP_USER_AGENT;
	if(empty($HTTP_USER_AGENT)) {
		logMessage("Отсутствует или пустой User-Agent");
		return false;
	}
	return true;
}

# Разрешающие фильтры
function isAllow()
{
	global $HTTP_USER_AGENT, $AB_IS_TOR, $AB_IS_USERAGENT;

	logMessage("" . mb_substr($_SERVER['REQUEST_URI'], 0, 255));
	logMessage("" . mb_substr($HTTP_USER_AGENT, 0, 255));

	# Проверка REQUSET_URI на исключения
	if(isWhiteListReauestUrl()) {
		return true;
	}

	# Проверка IP в черном списке
	if (blacklistIP($_SERVER['REMOTE_ADDR'])) {
		logMessage("IP-адрес найден в черном списке");
		DISPLAY_BLOCK_FORM_EXIT();
	}

	# Проверка на установленную метку
	if (isMarker()) {
		logMessage("Найден маркер");
		return true;
	}

	# Проверка IP на белый лист
	if (whitelistIP($_SERVER['REMOTE_ADDR'])) {
		logMessage("IP-адрес найден в белом списке");
		return true;
	}

	# Проверка на сущестование User-agent
	if($AB_IS_USERAGENT && !isUserAgent()) {
		addToBlacklist($_SERVER['REMOTE_ADDR'], 'not User-agent');
		DISPLAY_BLOCK_FORM_EXIT();
	}

	# Проверка, является ли пользовательский агент исключением
	if (isExcludedBotLegal($_SERVER['HTTP_USER_AGENT'])) {
		return true;
	}

	# Проверяем является ли пользователь индексирующим ботом Яндек, Гугл
	if (isIndexbot($_SERVER['REMOTE_ADDR'])) {
		logMessage("Индексирующий робот");

		# Добавляем айпи в белый список для производительности
		addToWhitelist($_SERVER['REMOTE_ADDR'], 'indexbot');

		return true;
	}

	# Проверка на принадлежность к Tor-сети
	try {
		if ($AB_IS_TOR && isTor($_SERVER['REMOTE_ADDR'])) {
			logMessage("IP-адрес является выходным узлом Tor");
			addToBlacklist($_SERVER['REMOTE_ADDR'], 'tor');
			DISPLAY_BLOCK_FORM_EXIT();
		}
	} catch (Exception $e) {
		logMessage("Error: " . $e->getMessage());
	}

	return false;
}

function validateDomain($domain)
{
	$pattern = '/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/';
	return preg_match($pattern, $domain);
}
