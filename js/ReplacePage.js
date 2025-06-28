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
        if (originalParent.nodeName !== targetParent.nodeName) continue;

        if (script.src) {
            // Для внешних скриптов
            newScript.src = script.src;
            targetParent.appendChild(newScript);
        } else {
            // Для inline-скриптов
            newScript.textContent = script.textContent;
            targetParent.appendChild(newScript);
        }

        // Переносим inline-обработчики событий (onclick, onload и т. д.)
        const elementsWithEvents = Array.from(dom.querySelectorAll('[onclick],[onload],[onchange]')); // добавьте другие события при необходимости
        for (const element of elementsWithEvents) {
            const newElement = targetParent.querySelector(`[data-original-id="${element.id}"]`) ||
                targetParent.querySelector(element.tagName.toLowerCase());

            if (newElement) {
                Array.from(element.attributes).forEach(attr => {
                    if (attr.name.startsWith('on')) {
                        newElement.setAttribute(attr.name, attr.value);
                    }
                });
            }
        }
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
export async function loadPage(url) {
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
