let fLoad = false; // защита от зацикливания
function loadLangScript(pathFile, callback = null) {
    var script = document.createElement('script');
    script.src = HTTP_ANTIBOT_PATH + pathFile;
    script.async = true;
    if (callback !== null) script.onload = callback;
    script.onerror = function () {
        if(!fLoad) {
            loadLangScript('js/lang/en.js', callbackLanguage);
            fLoad = true;
        } else {
            console.log("Error: Not load language script js/lang/ru.js");
        }
    };
    document.head.appendChild(script);
}

// Загрузился языковой модуль
function callbackLanguage() {
    document.title = (document.title == "Требуется внимание!") // костыль
        ? document.title = lang['block_title'] : document.title = lang['title'];
    document.querySelectorAll('[data-translate]').forEach(translateElement);
}

function puny_decode(x) {
    const BASE = 36; 
    const T_MIN = 1;
    const T_MAX = 26;
    const SKEW = 38;
    const DAMP = 700;
    const BIAS = 72;
    const MIN_CP = 128;
    const MAX_CP = 0x10FFFF;
    const SHIFT_BASE = BASE - T_MIN;
    const MAX_DELTA = SHIFT_BASE * T_MAX >> 1;
    const CP_HYPHEN = 0x2D;
    const CP_X = 0x78;
    const CP_N = 0x6E;

    function basic_from_cp(cp) {
        if (cp <= 90) {
            if (cp >= 65) {
                return cp - 65;
            } else if (cp >= 48 && cp <= 57) { 
                return cp - 22;
            }
        } else if (cp >= 97 && cp <= 122) { 
            return cp - 97;
        }
        throw new Error(`not alphanumeric ASCII: 0x${cp.toString(16)}`);
    }
    
    function trim_bias(k, bias) {
        let delta = k - bias;
        return delta <= 0 ? T_MIN : delta >= T_MAX ? T_MAX : delta;
    }

    function adapt(delta, n, first) {
        delta = Math.floor(delta / (first ? DAMP : 2));
        delta += Math.floor(delta / n);
        let k = 0;
        while (delta > MAX_DELTA) {
            delta = Math.floor(delta / SHIFT_BASE);
            k += BASE;
        }
        return k + Math.floor((1 + SHIFT_BASE) * delta / (delta + SKEW));
    }

    function explode_cp(s) {
        return [...s].map(x => x.codePointAt(0));
    }
    
    function has_label_ext(v) {
        return v.length >= 4 
            && (v[0] == CP_X || v[0] == 88)
            && (v[1] == CP_N || v[1] == 78)
            && v[2] == CP_HYPHEN
            && v[3] == CP_HYPHEN;
    }
    
    function decode(cps) {
        let pos = cps.lastIndexOf(CP_HYPHEN) + 1;
        let end = Math.max(0, pos - 1);
        let ret = cps.slice(0, end);
        let i = 0;
        let cp = MIN_CP;
        let bias = BIAS;
        while (pos < cps.length) {
            let prev = i;
            let w = 1;
            let k = BASE;
            while (true) {
                let basic = basic_from_cp(cps[pos++]);
                i += basic * w;
                let t = trim_bias(k, bias);
                if (basic < t) break;
                if (pos >= cps.length) throw new Error(`invalid encoding`);
                w *= BASE - t;
                k += BASE;
            }
            let len = ret.length + 1;
            bias = adapt(i - prev, len, prev == 0);
            cp += Math.floor(i / len);		
            if (cp > MAX_CP) throw new Error(`invalid encoding`);
            i %= len;
            ret.splice(i++, 0, cp);
        }	
        return ret;
    }
    
    // Разбиваем hostname на части по точкам
    const parts = typeof x === 'string' ? x.split('.') : [x];
    
    // Декодируем каждую часть
    const decodedParts = parts.map(part => {
        const cps = explode_cp(part);
        if (has_label_ext(cps)) {
            // Это Punycode, декодируем
            const decoded = decode(cps.slice(4));
            return String.fromCodePoint(...decoded);
        } else {
            // Это обычный ASCII, оставляем как есть
            return part;
        }
    });
    
    // Собираем обратно с точками
    return decodedParts.join('.');
}

// Функция для перевода текста
function translateElement(element) {
    const key = element.getAttribute('data-translate');
    if (key && lang[key]) {
        let text = lang[key];

        // Замена {domain} и других переменных
        const domain = puny_decode(window.location.hostname);
        text = text.replace(/{domain}/g, domain);

        element.innerHTML = text;
    }
}

(function () {
    const langCode = (navigator.language || navigator.userLanguage).substring(0, 2);
    loadLangScript('js/lang/' + langCode + '.js', callbackLanguage);
}());