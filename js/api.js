let FINGERPRINT = '';
let FRAME_RATE = 0;
let IS_LOAD = {}; // готовность всех модулей

/*
function isWebWorkerSupported() {
	return typeof Worker !== 'undefined';
}

let workerCount = 0;
function createWorker(countEnd) {
	countEnd = (countEnd == undefined) ? 10:countEnd;
	try {
		const worker = new Worker('benchmark.js');
		worker.postMessage('start');
		workerCount++;
		console.log(`Worker ${workerCount} started`);
		if(countEnd < workerCount) 
			createWorker();
	} catch (error) {
		console.error(`Failed to create Worker ${workerCount + 1}:`, error);
		console.log(`Maximum workers supported: ${workerCount}`);
	}
}

function startBanchmark() {
	if (isWebWorkerSupported()) {
		createWorker();
	} else {
		console.error('Web Workers are not supported in this browser.');
	}
}
*/

function refresh() {
	flagCloseWindow = false;

	const currentUrl = new URL(window.location.href);
	const ref = document.referrer || 'direct';
	let needReload = false;

	if (SAVE_REFERER) {
		localStorage.setItem('originalReferrer', ref);
	}

	// Передаем referrer через utm_referrer
	if (UTM_REFERRER && ref != 'direct') {
		// Удаляем служебные параметры антибота (если есть)
		//currentUrl.searchParams.delete('awaf_checked');

		// Добавляем ref, только если его ещё нет
		if (!currentUrl.searchParams.has('utm_referrer')) {
			currentUrl.searchParams.set('utm_referrer', encodeURIComponent(ref));
			needReload = true;
		}
	}

	if (needReload) {
		window.location.href = currentUrl.toString();
		// Фолбэк: если перезагрузки не произошло (из-за якоря)
		setTimeout(() => {
			if (window.location.href === currentUrl.toString()) {
				window.location.reload();
			}
		}, 100);
	} else
		window.location.reload();
}

function initFingerPrint() {
	if (typeof FingerprintJS !== 'undefined') {
		FingerprintJS.load()
			.then(fp => fp.get())
			.then(result => {
				FINGERPRINT = result.visitorId;
				IS_LOAD["initFingerPrint"] = true;
			});

	} else {
		console.error(callback + 'FingerprintJS no load');
	}
}

function callbackFrameRate() {
	if (typeof FrameRateJS !== 'undefined') {
		FrameRateJS.load()
			.then(result => {
				FRAME_RATE = result;
				IS_LOAD["callbackFrameRate"] = true;
			});
	} else {
		console.error(callback + 'FrameRateJS no load');
	}

}

function getObjectBrowser(obj, options = {}) {
	const {
		includeNull = false,
		includeEmpty = false
	} = options;

	// Явно исключаем HTMLAllCollection
	if (obj instanceof HTMLAllCollection) return undefined;

	// Проверка на другие нежелательные объекты
	const forbiddenTypes = [
		'[object HTMLAllCollection]',
		'[object HTMLCollection]',
		'[object NodeList]'
	];

	if (forbiddenTypes.includes(Object.prototype.toString.call(obj))) {
		return undefined;
	}

	if (obj === null) return includeNull ? null : undefined;
	if (typeof obj !== 'object') return obj;

	if (obj instanceof Date) return obj.toISOString();
	if (obj instanceof RegExp) return obj.toString();
	if (obj instanceof HTMLElement || obj instanceof Function) return undefined;

	const result = {};
	let hasValidProperties = false;

	// Создаем массив для хранения всех ключей
	const keys = [];

	// Собираем все перечисляемые свойства (включая унаследованные)
	for (const key in obj) {
		keys.push(key);
	}

	// Добавляем символьные свойства
	const symbols = Object.getOwnPropertySymbols(obj);
	for (const sym of symbols) {
		keys.push(sym);
	}

	for (const key of keys) {
		try {
			// Пропускаем специальные свойства
			if (key === '__proto__' || key === 'constructor') continue;

			// Безопасное получение значения свойства
			const value = (typeof key === 'symbol')
				? obj[key]
				: obj[key];

			// Пропускаем функции и DOM-элементы
			if (typeof value === 'function' || value instanceof HTMLElement) continue;

			// Обрабатываем только примитивы
			if (value !== null && typeof value === 'object') continue;

			if (value !== undefined && (includeNull || value !== null)) {
				// Для символов используем строковое представление
				const resultKey = (typeof key === 'symbol')
					? `Symbol(${key.description || ''})`
					: key;

				result[resultKey] = value;
				hasValidProperties = true;
			}
		} catch (e) {
			continue;
		}
	}

	return hasValidProperties ? result : (includeEmpty ? {} : undefined);
}

function ymc(metrika, ip) {
	if (typeof ym === 'function') return;

	try {
		(function (m, e, t, r, i, k, a) {
			m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments) };
			m[i].l = 1 * new Date();
			for (var j = 0; j < document.scripts.length; j++) { if (document.scripts[j].src === r) { return; } }
			k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a)
		})
			(window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

		ym(metrika, "init", {
			clickmap: true,
			trackLinks: true,
			accurateTrackBounce: true,
			webvisor: true,
			params: { ip: ip }
		});
	} catch (e) { }
}

/**
 * Проверяет на включенные куки
 * @returns 
 */
function сheckCookie() {
	document.cookie = "testcookie=1; SameSite=Lax; path=/";
	const cookiesEnabled = document.cookie.includes("testcookie=");
	document.cookie = "testcookie=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/";

	return cookiesEnabled;
}
/**
 * Возвращает true если это старый движок Mozilla или BAS-браузер
 */
function isBas() {
	Object.create(location.reload);
	return Reflect.ownKeys(location.reload).length === 3;
}

// Убирает индикатор проверки
function spinnerNone() {
	lspinner.style.display = "none";
	blockHTTPSecurity.style.display = "none";
}

function spinnerShow() {
	lspinner.style.display = "";
	blockHTTPSecurity.style.display = "none";
}

/**
 * Подключает js скрипт к странице
 * @param pathFile Относительный путь к файлу js
 */
function loadScript(pathFile, callback = null) {
	if (callback != null) {
		const callbackName = callback.name || 'anonymous';
		IS_LOAD[callbackName] = false;
	}

	var script = document.createElement('script');
	script.src = HTTP_ANTIBOT_PATH + pathFile;
	script.async = true;
	if (callback !== null) script.onload = callback;
	script.onerror = function () {
		console.error('Error load: ' + pathFile);
	};
	document.head.appendChild(script);
}

// Действие после успешной проверки
function allow() {
	if (METRIKA_ID != '') {
		try {
			ym(METRIKA_ID, 'reachGoal', 'onclickcapcha');
		} catch (e) { }
	}
	form.style.display = "none";
	lspinner.style.display = "none";
	blockHTTPSecurity.style.display = "none";
	blockSuccessful.style.display = "block";
	setTimeout(refresh, 1000);
}

// Действие после блокировки
function block() {
	flagCloseWindow = false;
	const urlString = window.location.href;
	const url = new URL(urlString);
	const protocol = url.protocol; // "https:"
	const domain = url.hostname; // "example.com"
	window.location.href = protocol + '//' + domain + '/?awafblock';
}



function checkBot(func) {
	var xhr = new XMLHttpRequest();
	var visitortime = new Date();

	let obj = {
		func: func,
		csrf_token: CSRF,
	};

	if (func == 'checks') {
		obj2 = { // Данные для отправки
			datetime: {
				now: visitortime.toISOString(),
				timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'Unknown',
				offsetHours: -(visitortime.getTimezoneOffset() / 60),
			},
			clientWidth: document.documentElement.clientWidth,
			clientHeight: document.documentElement.clientHeight,
			screenWidth: window.screen.width,
			screenHeight: window.screen.height,
			pixelRatio: window.devicePixelRatio || 1,
			colorDepth: window.screen.colorDepth,
			pixelDepth: window.screen.pixelDepth,
			java: window.java ? 1 : 0,
			referer: document.referrer,
			document: getObjectBrowser(document),
			window: getObjectBrowser(window),
			navigator: getObjectBrowser(navigator),
			screen: getObjectBrowser(window.screen),
			location: getObjectBrowser(window.location),
			fingerPrint: FINGERPRINT,
			isBas: isBas(),
			isFrame: window.top === window.self,
			frameRate: FRAME_RATE,

		};
		Object.assign(obj, obj2);
	}
	// console.log(obj);

	let data = null;
	try {
		data = JSON.stringify(obj);
	} catch (e) {
		console.error('Failed to stringify data:', e);
	}

	xhr.open('POST', HTTP_ANTIBOT_PATH + 'xhr.php', true);
	xhr.setRequestHeader('Content-Type', 'application/json');

	xhr.onload = async function () {
		if (xhr.status >= 200 && xhr.status < 300) {
			var data = JSON.parse(xhr.responseText);
			CSRF = data.csrf_token;

			if (data.status == 'captcha') {
				// loadScript('js/benchmark.js', null);
				displayCaptcha();
			} else if (data.status == 'allow') {
				allow();
			} else if (data.status == 'block') {
				setTimeout(block, 1000);
			} else if (data.status == 'refresh') {
				setTimeout(refresh, 1000);
			} else if (data.status == 'fail') {
				console.log(data);
			} else {
				console.log(data);
			}
		} else {
			console.error('Request failed with status:', xhr.status, xhr.statusText);
		}
	};

	xhr.onerror = function () {
		console.error('Network error occurred');
	};

	xhr.send(data);
}

/* MAIN */

if (METRIKA_ID != '') {
	ymc(METRIKA_ID, REMOTE_ADDR);
}

if (!сheckCookie()) {
	spinnerNone();
	noscript = document.querySelectorAll('noscript');
	const div = document.createElement('div');
	div.innerHTML = noscript[0].innerHTML;
	noscript[0].replaceWith(div);
} else {
	// Проверяет готовность всех модулей
	const intervalId = setInterval(async () => {
		try {
			if (Object.keys(IS_LOAD).length > 0) {
				let found = true;
				for (const value of Object.values(IS_LOAD)) {
					if (!value) {
						found = false;
						break;
					}
				}
				if (found) {
					checkBot('checks');
					clearInterval(intervalId);
				}
			}
		} catch (error) {
			console.error('Ошибка:', error);
		}
	}, 500);

	loadModules();
}