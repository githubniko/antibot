var FrameRateJS = (function (exports) {
    'use strict';

    /**
     * Вычисляет медианное значение из массива чисел
     * @param {number[]} values - массив значений FPS
     * @returns {number} - медианное значение
     */
    function calculateMedian(values) {
        if (values.length === 0) return 0;
        
        const sorted = values.slice().sort((a, b) => a - b);
        const middle = Math.floor(sorted.length / 2);
        
        if (sorted.length % 2 === 0) {
            return Math.round((sorted[middle - 1] + sorted[middle]) / 2);
        }
        return sorted[middle];
    }

    /**
     * Измеряет FPS (кадры в секунду) в браузере
     * @param {Object} options - параметры измерения
     * @param {number} options.duration - общая длительность измерения в мс (по умолчанию 3000)
     * @param {number} options.sampleInterval - интервал между замерами FPS в мс (по умолчанию 1000)
     * @param {number} options.timeout - максимальное время ожидания в мс (по умолчанию 5000)
     * @returns {Promise<number>} - Promise с медианным значением FPS
     */
    function load(options = {}) {
        const {
            duration = 500,
            sampleInterval = 100,
            timeout = 3000
        } = options;

        // Проверка корректности параметров
        if (sampleInterval > duration) {
            console.warn('FrameRateJS: sampleInterval больше duration - будет не более 1 замера');
        }

        let frameCount = 0;
        let startTime = null;
        let lastSampleTime = null;
        let samples = [];
        let timeoutId = null;
        let rafId = null;
        let isActive = false;
        let waitForVisibleHandler = null;
        let resolvePromise = null;

        /**
         * Очищает все ресурсы и обработчики событий
         */
        const cleanup = () => {
            if (timeoutId) {
                clearTimeout(timeoutId);
                timeoutId = null;
            }
            
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = null;
            }
            
            document.removeEventListener('visibilitychange', handleVisibilityChange);
            window.removeEventListener('beforeunload', handleBeforeUnload);
            window.removeEventListener('pagehide', handleBeforeUnload);
            
            if (waitForVisibleHandler) {
                document.removeEventListener('visibilitychange', waitForVisibleHandler);
                waitForVisibleHandler = null;
            }
            
            isActive = false;
        };

        /**
         * Завершает измерение и возвращает результат
         * @param {number|null} forcedResult - принудительный результат (например, при выгрузке страницы)
         */
        const completeMeasurement = (forcedResult = null) => {
            if (!resolvePromise) return;
            
            cleanup();
            
            if (forcedResult !== null) {
                resolvePromise(forcedResult);
                return;
            }
            
            const result = samples.length > 0 
                ? calculateMedian(samples) 
                : 0;
            
            resolvePromise(result);
        };

        /**
         * Обработчик выгрузки страницы
         */
        const handleBeforeUnload = () => {
            completeMeasurement(0);
        };

        /**
         * Обработчик видимости страницы
         */
        const handleVisibilityChange = () => {
            if (document.hidden && isActive) {
                // Страница скрыта - приостанавливаем измерение
                stopMeasurement();
            } else if (!document.hidden && !isActive && startTime) {
                // Страница снова видна - возобновляем с того же места
                startMeasurement();
            }
        };

        /**
         * Останавливает измерение
         */
        const stopMeasurement = () => {
            if (rafId) {
                cancelAnimationFrame(rafId);
                rafId = null;
            }
            isActive = false;
        };

        /**
         * Запускает измерение
         */
        const startMeasurement = () => {
            if (isActive) return;
            
            // Если это первый запуск - инициализируем время
            if (startTime === null) {
                startTime = performance.now();
                lastSampleTime = startTime;
                frameCount = 0;
                samples = [];
            }
            
            isActive = true;
            rafId = requestAnimationFrame(checkFPS);
        };

        /**
         * Основная функция проверки FPS
         */
        const checkFPS = (currentTime) => {
            if (!isActive) return;

            frameCount++;
            
            const timeFromStart = currentTime - startTime;
            
            // Проверяем, не прошло ли общее время измерения
            if (timeFromStart >= duration) {
                completeMeasurement();
                return;
            }
            
            const delta = currentTime - lastSampleTime;
            
            // Делаем замер FPS через заданный интервал
            if (delta >= sampleInterval) {
                // Рассчитываем FPS на основе прошедшего интервала
                const fps = Math.round((frameCount * 1000) / delta);
                
                // Сохраняем значение FPS
                samples.push(fps);
                
                // Сбрасываем счётчик для следующего интервала
                frameCount = 0;
                lastSampleTime = currentTime;
            }
            
            rafId = requestAnimationFrame(checkFPS);
        };

        return new Promise((resolve) => {
            resolvePromise = resolve;

            // Устанавливаем таймаут на случай зависания
            timeoutId = setTimeout(() => {
                if (isActive) {
                    completeMeasurement();
                }
            }, timeout);

            // Добавляем обработчики событий
            document.addEventListener('visibilitychange', handleVisibilityChange);
            window.addEventListener('beforeunload', handleBeforeUnload);
            window.addEventListener('pagehide', handleBeforeUnload);

            // Начинаем измерение только если страница видима
            if (!document.hidden) {
                startMeasurement();
            } else {
                // Ждём пока страница станет видимой
                waitForVisibleHandler = () => {
                    if (!document.hidden) {
                        document.removeEventListener('visibilitychange', waitForVisibleHandler);
                        waitForVisibleHandler = null;
                        startMeasurement();
                    }
                };
                document.addEventListener('visibilitychange', waitForVisibleHandler);
            }
        });
    }

    exports.load = load;
    
    return exports;
})({});

// Примеры использования:
/*
// Базовое использование с настройками по умолчанию
FrameRateJS.load().then(fps => {
    console.log('Средний FPS:', fps);
});

// Расширенное использование с кастомными параметрами
FrameRateJS.load({
    duration: 5000,        // 5 секунд тест
    sampleInterval: 500     // замеры каждые 500 мс
}).then(fps => {
    console.log('Медианный FPS за 5 секунд:', fps);
});

*/