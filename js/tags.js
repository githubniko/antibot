let CSRF = '';

/**
 * Подключает js скрипт к странице
 * @param pathFile Относительный путь к файлу js
 */
function loadScript(pathFile, callback = null) {
    var script = document.createElement('script');
    script.type = "text/javascript";
    script.src = HTTP_ANTIBOT_PATH + pathFile;
    script.async = true;
    if (callback !== null) script.onload = callback;
    script.onerror = function () {
        console.error('Error load: ' + pathFile);
    };
    document.head.appendChild(script);
}

function getScriptPath() {
    const script = document.currentScript;
    if (script) {
        const scriptUrl = script.src;
        const parts = scriptUrl.split('/');
        // Убираем последние два элемента ('js' и 'tags.js')
        const path = parts.slice(0, -2).join('/') + '/';
        return path;
    }
    return null;
}

function loading(func, callback) {
    let xhr = new XMLHttpRequest();

    let obj = {
        func: func,
        csrf_token: CSRF,
    };

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
            const data = JSON.parse(xhr.responseText);
            CSRF = data.csrf_token;
            // console.log(data);

            if (data.func == 'load_module') {
                if (data.modules != undefined) {
                    data.modules.forEach(element => {
                        loadScript("js/" + element + ".js");
                    });
                }
            }

            if (callback != undefined) {
                callback(data);
            }
        }
        else {
            console.error('Request failed with status:', xhr.status, xhr.statusText);
        }
    };

    xhr.onerror = function () {
        console.error('Network error occurred');
    };

    xhr.send(data);
}

const HTTP_ANTIBOT_PATH = getScriptPath();
loading("load_module");

