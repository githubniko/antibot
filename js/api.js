const head2 = document.getElementById("pSht7");
const form = document.getElementById("uHkM6");
const lspinner = document.getElementById("InsTY1");
const input = document.getElementById("uiEr3");
const blockSuccessful = document.getElementById("tWuBw3");
const blockHTTPSecurity = document.getElementById("LfAMd3");
const blockInput = document.getElementById("ihOWn1");
const blockVerifying = document.getElementById("verifying");
const blockFail = document.getElementById("fail");
let CSRF = "";
let flagCloseWindow = true;
let FINGERPRINT = '';


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
	window.location.href = window.location.href;
}
function pageBlock() {
	flagCloseWindow = false;
	const urlString = window.location.href;
	const url = new URL(urlString);
	const protocol = url.protocol; // "https:"
	const domain = url.hostname;   // "example.com"
	window.location.href = protocol + '//' + domain + '/?awafblock';
}

/**
 * Подключает js скрипт к странице
 * @param pathFile Относительный путь к файлу js
 */
function loadScript(pathFile, callback) {
	var script = document.createElement('script');
	script.src = HTTP_ANTIBOT_PATH + pathFile;
	script.async = true;
	script.onload = callback;
	script.onerror = function () {
		console.error('Error load: ' + pathFile);
	};
	document.head.appendChild(script);
}

function initFingerPrint() {
	if (typeof FingerprintJS !== 'undefined') {
		FingerprintJS.load()
			.then(fp => fp.get())
			.then(result => {
				FINGERPRINT = result.visitorId;
				checkBot('checks');
			});

	} else {
		console.error(callback + 'FingerprintJS no load');
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

function checkBot(func) {
	var xhr = new XMLHttpRequest();
	var visitortime = new Date();

	let obj = {
		func: func == undefined ? 'csrf_token' : func,
		csrf_token: CSRF,
		mainFrame: window.top === window.self,
	};

	if (func !== undefined) {
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
			fingerPrint: FINGERPRINT,
		};
		Object.assign(obj, obj2);
	}
	console.log(obj);

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
			if (CSRF == undefined || CSRF == '') {
				console.log('Error getting csrf_token');
				return;
			}

			if (data.func == 'csrf_token') {
				loadScript('js/fp.min.js', initFingerPrint);
			}
			else if (data.status == 'captcha') {
				lspinner.style.display = "none";
				form.style.display = "grid";
				blockInput.style.display = "none";
				blockVerifying.style.display = "";

				// loadScript('js/benchmark.js', null);
				displayCaptcha();
			}
			else if (data.status == 'allow') {
				form.style.display = "none";
				lspinner.style.display = "none";
				blockHTTPSecurity.style.display = "none";
				blockSuccessful.style.display = "block";
				setTimeout(refresh, 1000);
			}
			else if (data.status == 'block') {
				setTimeout(pageBlock, 1000);
			}
			else if (data.status == 'fail') {
				form.style.display = "grid";
				lspinner.style.display = "none";
				blockHTTPSecurity.style.display = "none";
				blockFail.style.display = "grid";
				blockInput.style.display = "none";
			}
			else if (data.status == 'refresh') {
				setTimeout(refresh, 1000);
			}
			else {
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

function displayCaptcha() {
	input.addEventListener('click', function (event) {
		if (this.checked) {
			if (METRIKA_ID != '') {
				try { ym(METRIKA_ID, 'reachGoal', 'onclickcapcha'); } catch (e) { }
			}
			blockInput.style.display = "none";
			blockVerifying.style.display = "";
			checkBot('set-marker');
		}
	});
	window.onbeforeunload = function (e) {
		if (flagCloseWindow) {
			checkBot('win-close');
		}
	};

	displayNone();
	head2.textContent = "Подтвердите, что вы человек, выполнив указанное действие.";
	form.style.display = "grid";
	blockInput.style.display = "grid";
}

function displayNone() {
	lspinner.style.display = "none";
	blockHTTPSecurity.style.display = "none";
	blockInput.style.display = "none";
	blockVerifying.style.display = "none";
	input.checked = '';
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



if (METRIKA_ID != '') {
	ymc(METRIKA_ID, REMOTE_ADDR);
}

checkBot();