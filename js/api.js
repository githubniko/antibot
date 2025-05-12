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

function initFingerPrint()
{
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

/**
 * Удаляет/очищает текущие link, script, style и т.п.
 */
function clearResources() {
	// Удаляем все script-теги
	document.querySelectorAll('script').forEach(script => {
		script.remove();
	});

	document.querySelectorAll('noscript').forEach(script => {
		script.remove();
	});

	// Удаляем все link[rel="stylesheet"]
	document.querySelectorAll('link').forEach(link => {
		link.remove();
	});

	// Удаляем все style-теги
	document.querySelectorAll('style').forEach(style => {
		style.remove();
	});

	// Удаляем все meta-теги
	document.querySelectorAll('meta').forEach(meta => {
		meta.remove();
	});
}

/**
 * Копирует и загружает новые css
 * @param {DOM источника} dom 
 */
function loadCSS(dom) {
	// 
	const loadedStyles = new Set();
	dom.querySelectorAll('link[rel="stylesheet"]').forEach(link => {
		if (!loadedStyles.has(link.href)) {
			const newLink = link.cloneNode(true);
			document.head.appendChild(newLink);
			loadedStyles.add(link.href);
		}
	});

	dom.querySelectorAll('style').forEach(style => {
		const newStyle = document.createElement('style');
		newStyle.textContent = style.textContent;
		document.head.appendChild(newStyle);
	});

}

/**
 * Загружает скрипты, сохраняя их исходное расположение в DOM
 * @param {Document|Element} dom - Документ или элемент, содержащий скрипты
 * @param {Object} [targetParent] - Родительский объект (document.body || document.head)
 * @returns {Promise<void>}
 */
async function loadJS(dom, targetParent) {
	const scripts = Array.from(dom.querySelectorAll('script:not([data-no-load])'));

	for (const script of scripts) {
		const newScript = document.createElement('script');

		// Копируем все атрибуты
		Array.from(script.attributes).forEach(attr => {
			newScript.setAttribute(attr.name, attr.value);
		});

		// Определяем оригинальное расположение
		const originalParent = script.parentNode;
		if(originalParent.nodeName != targetParent.nodeName)
			return;

		if (script.src) {
			// Для внешних скриптов
			newScript.src = script.src;
			targetParent.appendChild(newScript);
		} else {
			// Для inline-скриптов
			newScript.textContent = script.textContent;
			targetParent.appendChild(newScript);
		}

		// Сохраняем позицию, если это важно
		//   if (script.nextSibling) {
		// 	console.log(newScript);
		// 	console.log(targetParent);
		// 	targetParent.insertBefore(newScript, script.nextSibling);
		//   }
		targetParent.appendChild(newScript);
	}
}

/**
 * Удаление текущих событий и подписок
 */
function clearEvents() {
	// Очищаем все стандартные DOM-события
	const cleanElement = (element) => {
		if (!element) return;

		const clone = element.cloneNode(false); // Клонируем без обработчиков
		element.parentNode?.replaceChild(clone, element);
	};

	// Очищаем body и все его дочерние элементы
	cleanElement(document.body);

	// Очищаем setTimeout/setInterval
	let lastId = setTimeout(() => { }, 0);
	while (lastId--) {
		clearTimeout(lastId);
		clearInterval(lastId);
	}
}

/**
 * Замена страницы
 * @param {URL сайта} url 
 */
async function loadPage(url) {
	const response = await fetch(url);
	const html = await response.text();

	// Делеаем через iframe т.к. способы ew DOMParser() переносит метатеги в body
	const iframe = document.createElement('iframe');
	iframe.style.display = 'none';
	document.body.appendChild(iframe);
	iframe.contentDocument.open();
	iframe.onload = () => {
		const doc = iframe.contentDocument;

		clearEvents();
		clearResources(); // Удаляем текущие JS/CSS 
		loadCSS(doc);

		// Title
		const newTitle = doc.querySelector('title')?.textContent || document.title;
		document.title = newTitle;

		loadJS(doc, document.head);
		// Обновление контента
		document.body.innerHTML = doc.body.innerHTML;

		loadJS(doc, document.body); 
	};
	iframe.contentDocument.write(html);
	iframe.contentDocument.close();
}

function checkBot(func) {
	var xhr = new XMLHttpRequest();

	var data = JSON.stringify({ // Данные для отправки
		screenWidth: window.screen.width,
		screenHeight: window.screen.height,
		pixelRatio: window.devicePixelRatio || 1,
		referer: document.referrer,
		mainFrame: window.top === window.self,
		func: func == undefined ? 'csrf_token' : func,
		fingerPrint: FINGERPRINT,
		csrf_token: CSRF,
	});

	xhr.open('POST', HTTP_ANTIBOT_PATH + 'xhr.php', true);
	xhr.setRequestHeader('Content-Type', 'application/json');

	xhr.onload = function () {
		if (xhr.status >= 200 && xhr.status < 300) {
			var data = JSON.parse(xhr.responseText);
			if (data.func == 'csrf_token') {
				CSRF = data.csrf_token;
				if(CSRF == undefined || CSRF == '') {
					console.log('Error getting csrf_token');
					return;
				}
				loadScript('js/fp.min.js', initFingerPrint);
			}
			else if (data.status == 'captcha') {
				CSRF = data.csrf_token;
				displayCaptcha();
			}
			else if (data.status == 'allow') {
				form.style.display = "none";
				lspinner.style.display = "none";
				blockHTTPSecurity.style.display = "none";
				blockSuccessful.style.display = "block";
				if(data.refsave != undefined && data.refsave) {
					loadPage(window.location.href);
				} else {
					setTimeout(refresh, 1000);
				}
			}
			else if (data.status == 'block') {
				setTimeout(refresh, 1000);
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
	head2.textContent = "Подтвердите, что вы человек, выполнив указанное действие.";
	form.style.display = "grid";
	lspinner.style.display = "none";
	blockHTTPSecurity.style.display = "none";

	input.addEventListener('click', function (event) {
		if (this.checked) {
			ym(METRIKA_ID,'reachGoal','onclickcapcha');
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
}

checkBot();