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
				setTimeout(refresh, 1000);
			}
			else if (data.status == 'block') {
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