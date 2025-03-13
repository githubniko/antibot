const head2 = document.getElementById("pSht7");
const form = document.getElementById("uHkM6");
const lspinner = document.getElementById("InsTY1");
const input = document.getElementById("uiEr3");
const blockSuccessful = document.getElementById("tWuBw3");
const blockHTTPSecurity = document.getElementById("LfAMd3");
const blockInput = document.getElementById("ihOWn1");
const blockVerifying = document.getElementById("verifying");
const blockFail = document.getElementById("fail");
let KEY_ID = "";

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

function checkBot(func) {
	fetch(HTTP_ANTIBOT_PATH + 'check_bot.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            screenWidth: window.screen.width,
            screenHeight: window.screen.height,
            pixelRatio: window.devicePixelRatio || 1,
			referrer: document.referrer,
			mainFrame: window.top === window.self,
			func: func == undefined ? 'check':func,
			keyID: KEY_ID,
        })
    })
	.then(response => {
		if (!response.ok) {
			throw new Error('Network response was not ok ' + response.statusText);
		}
		return response.json();
	})
	.then(data => {
		if(data.status == 'captcha') {
			KEY_ID = data.keyID;
			displayCaptcha();
		}
		else if(data.status == 'allow') {
			form.style.display = "none";
			lspinner.style.display = "none";
			blockHTTPSecurity.style.display = "none";
			blockSuccessful.style.display = "block";
			setTimeout(() => { window.location.href = window.location.href; }, 1000);
		}
		else if(data.status == 'block') {
			setTimeout(() => { window.location.href = window.location.href; }, 1000);
		}
	})
	.catch(error => {
		console.log(error);
	});

}

function displayCaptcha()
{
	head2.textContent = "Подтвердите, что вы человек, выполнив указанное действие.";
	form.style.display = "grid";
	lspinner.style.display = "none";
	blockHTTPSecurity.style.display = "none";
	
	input.addEventListener('click', function(event) { 
		if (this.checked) {
			blockInput.style.display = "none";
			blockVerifying.style.display = "";
			checkBot('set-marker');
		}
	});
	window.onbeforeunload = function(e) {
		checkBot('win-close');
	  };
}

checkBot();