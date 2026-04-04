let FINGERPRINT;
/**
 * Подключает Яндекс Метрику
 * @param {ID метрики} metrika 
 * @returns 
 */
function ymc(metrika, ip) {
    try {
        const originalReferrer = localStorage.getItem('originalReferrer')
            || document.referrer
            || '';
        localStorage.removeItem('originalReferrer');

        let params = { ip: ip };

        if (typeof ym === 'function') {
            ym(metrika, 'params', params); // Передача параметров без иницилиализации
            return;
        }

        (function (m, e, t, r, i, k, a) {
            m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments) };
            m[i].l = 1 * new Date();
            for (var j = 0; j < document.scripts.length; j++) { if (document.scripts[j].src === r) { return; } }
            k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a)
        })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=' + metrika, 'ym');

        ym(metrika, 'init', { ssr: true, webvisor: true, clickmap: true, referrer: originalReferrer, url: location.href, accurateTrackBounce: true, trackLinks: true, params: params });

    } catch (e) { console.log(e); }
}


function callbackFP() {
    if (typeof FingerprintJS !== 'undefined') {
        FingerprintJS.load()
            .then(fp => fp.get())
            .then(result => {
                FINGERPRINT = result.visitorId;
            });

    } else {
        console.error(callback + 'FingerprintJS no load');
    }
}

function callbackMetrika(data) {
    if (data.ip == undefined) {
        console.log("Error: `data.ip` not found");
        return;
    }

    if (data.metrika == undefined) {
        console.log("Error: `data.metrika` not found");
        return;
    }

    if (data.metrika == "")
        return;

    ymc(data.metrika, data.ip);

    if (data.fp) {
        loadScript("js/fp.min.js", callbackFP);
        const intervalId = setInterval(async () => {
            if (FINGERPRINT) {
                ym(data.metrika, 'params', { fp: FINGERPRINT }); // отправляем доп. параметры
                clearInterval(intervalId);
            }
        }, 500);
        return;
    }
}

loading("get_param", callbackMetrika);

