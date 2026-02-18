<?php
/*
 * @author EgorNiKO <niko_egor@mail.ru>
 * @repository https://github.com/githubniko/antibot
 *
 * @copyright Copyright (c) 2025, EgorNiKO. All rights reserved.
 * @license MIT License
 */

namespace WAFSystem;

/**
 * Класс кеширует страницу. В качестве ключа используется URI
 */
class PageCache
{
    private $Config;
    private $WAFSystem;

    private $driver;
    private $keyCache = ""; // ключ созданный из URI
    public $enabled = false;

    private $configFile = "cache.ini";
    private $cacheDir = 'cache/pages/'; //  Директория для хранения кэша
    private $cacheTime = 3600; // Время жизни кэша в секундах (по умолчанию 3600)
    private $excludeUrls = [];
    private $clearParams = [];
    private $allowedParams = [];

    public function __construct(WAFSystem $wafsystem)
    {
        $this->WAFSystem = $wafsystem;

        $this->Config = Config::getInstance(
            $wafsystem->Config->DOCUMENT_ROOT,
            $wafsystem->Config->ANTIBOT_PATH,
            $this->configFile
        );

        # вкл/выкл защиты
        $this->enabled = $this->Config->init('main', 'enabled', $this->enabled, 'вкл/выкл');
        if (!$this->enabled) return;

        $this->cacheDir = $wafsystem->Config->CachePath . "pages/";

        $this->driver = new \Cache\FileCacheDriver($this->cacheDir);
        if (!$this->driver->isAvailable()) {
            throw new \RuntimeException('Cache driver is not available');
        }

        $this->cacheTime = $this->Config->init('main', 'cache_time', $this->cacheTime, 'Время жизни кэша в секундах');
        $excludeUrls = $this->Config->init(
            'main',
            'exclude_url',
            [
                'admin/',
                'login/'
            ],
            "URL для исключения из кэширования, через запятую"
        );
        $this->excludeUrls = is_array($excludeUrls) ? $excludeUrls : [$excludeUrls];

        $clearParams = $this->Config->init('main', 'clear_params', ['utm_*',], " Очистить параметры, через запятую");
        $this->clearParams = is_array($clearParams) ? $clearParams : [$clearParams];

        $allowedParams = $this->Config->init('main', 'allowed_params', [], " Разрешенные параметры, остальные будут отбрасываться, через запятую");
        $this->allowedParams = is_array($allowedParams) ? $allowedParams : [$allowedParams];

        $this->generateCacheKey();
    }

    /**
     * Проверяет, нужно ли кэшировать текущую страницу
     * @return bool
     */
    private function shouldCache()
    {
        // Не кэшируем POST запросы
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            return false;
        }

        // Не кэшируем для авторизованных пользователей
        // !!! РЕАЛИЗОВАТЬ

        // Проверяем исключенные URL
        $currentUrl = $_SERVER['REQUEST_URI'];
        foreach ($this->excludeUrls as $url) {
            if (strpos($currentUrl, $url) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Генерирует имя файла кэша на основе URL
     * @return void
     */
    private function generateCacheKey()
    {
        $url = $_SERVER['REQUEST_URI'];
        $domain = $_SERVER['SERVER_NAME'];

        // Валидация URL
        if (!is_string($url) || $url === '') {
            throw new \RuntimeException('Invalid URL');
        }

        // Удаляем исключенные параметры из URL
        $parsedUrl = parse_url($url);
        if ($parsedUrl === false) {
            throw new \RuntimeException('Failed to parse URL');
        }

        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $query = isset($parsedUrl['query']) ? $parsedUrl['query'] : '';

        if ($query) {
            mb_regex_encoding('UTF-8');
            parse_str($query, $params);

            // Удаляем исключенные параметры
            foreach ($this->clearParams as $key) {
                if (isset($params[$key])) {
                    unset($params[$key]);
                    continue;
                }

                if (strpos($key, '*') !== false) { // если есть регулярка
                    $pattern = str_replace("*", ".*", $key);
                    foreach ($params as $param => $value) {
                        // Сначала пробуем без регулярного выражения для ускорения процесса
                        if (preg_match("/^$pattern$/iu", $param) === 1) {
                            unset($params[$param]);
                        }
                    }
                }
            }

            // Поиск запрещенных параметров
            if (sizeof($this->allowedParams) > 0) {
                $invalidParams = []; // Массив для сбора недопустимых параметров
                foreach ($params as $param => $value) {
                    $found = false;

                    foreach ($this->allowedParams as $key) {
                        $pattern = str_replace("*", ".*", $key);
                        if (preg_match("/^$pattern$/iu", $param) === 1) {
                            $found = true;
                            break;
                        }
                    }

                    if (!$found)
                        $invalidParams[] = $param; // Добавляем параметр в список недопустимых

                }
                // Если есть недопустимые параметры, выбрасываем исключение с их перечислением
                if (!empty($invalidParams)) {
                    $message = "The URL contains invalid parameter(s): `" . implode("`, `", $invalidParams) . "`";
                    $this->WAFSystem->GrayList->add($this->WAFSystem->Profile->IP, $message);
                    throw new \RuntimeException($message);
                }
            }

            // Сортируем параметры по ключам для консистентности
            if (!empty($params)) {
                ksort($params);
                $path .= '?' . http_build_query($params);
            }
        }

        // Создаем хеш от URL
        $this->keyCache = md5($domain . $path);
    }

    /**
     * Проверяет существование и актуальность кэша
     * @return bool|string
     */
    private function getCache()
    {
        if (!$this->shouldCache()) {
            return false;
        }

        $data = $this->driver->get($this->keyCache);

        if ($data === false) {
            return false; // Нет записи в кэше
        }

        if (!isset($data['expires']) || $data['expires'] < time()) {
            return false; // Запись устарела
        }

        // Если нет контента или заголовков
        if (!isset($data['content']) || !isset($data['headers'])) {
            return false;
        }

        // Восстанавливаем заголовки
        foreach ($data['headers'] as $value)
            header($value);

        // Уведомляем браузер о кешировании
        header("Cache-Control: public, max-age=" . $this->cacheTime . ", must-revalidate");
        header("Last-Modified: " . gmdate('D, d M Y H:i:s', $data['expires']) . " GMT");
        header_remove("Pragma");

        return base64_decode($data['content']);
    }

    /**
     * Сохраняет контент в кэш
     * @param string $content Контент для сохранения
     */
    private function setCache($content)
    {
        if (!$this->shouldCache()) return "";

        // Не кешировать пустой контент, т.к. скорее всего это редирект
        if (empty($content)) return "";

        $date = [
            'domain' => $this->WAFSystem->Profile->Host,
            'uri' => $this->WAFSystem->Profile->REQUEST_URI,
            'headers' => $this->getHeaders(),
            'content' => base64_encode($content),
            'expires' => time() + $this->cacheTime,
        ];

        return $this->driver->set($this->keyCache, $date, $this->cacheTime);
    }

    /**
     * Получает заголовки с фильтрацией чувствительных данных
     */
    private function getHeaders()
    {
        // Белый список - только эти заголовки кешируем
        $whitelist = [
            'Content-Type:',
            'Cache-Control:',
            'Content-Encoding:',
            'Content-Language:',
        ];

        $headers_list = headers_list();
        if (empty($headers_list)) {
            return [];
        }

        $pattern = '/^(' . implode('|', array_map('preg_quote', $whitelist)) . ')/i';
        $headers = preg_grep($pattern, $headers_list);

        return array_values($headers); 
    }

    /**
     * Возвращает страницу из кеша или инклуда
     * @return string|null
     */
    public function Open()
    {
        // Проверяем наличие кэша
        $cachedContent = $this->getCache();
        if ($cachedContent !== false) {
            header("X-Cache: HIT");
            echo $cachedContent;
            exit;
        }

        $fileInclude = $_SERVER["DOCUMENT_ROOT"] . "/index.php.origin";
        if (!file_exists($fileInclude)) {
            throw new \RuntimeException("Original index file not found: " . $fileInclude);
        }

        $content = "";
        ob_start(function ($buffer) use (&$content) {
            $content .= $buffer;
            return '';
        }, 0);

        // На случай если внутри include есть выводы exit/die()
        register_shutdown_function(function () use (&$content) {
            while (ob_get_level() > 0)
                ob_end_clean();

            header('X-Cache: MISS');
            $this->setCache($content);
            echo $content;
        });

        include $fileInclude;
        ob_end_clean();

        header('X-Cache: MISS'); // Отправляем заголовки

        if (empty($content))
            return ""; // Сразу выходим, чтобы не насиловать систему

        $this->setCache($content);

        echo $content;
    }
}
