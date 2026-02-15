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
     * @return string
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
        $hash = md5($domain . $path);

        return $hash;
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

        try {
            $key = $this->generateCacheKey();
        } catch (\Exception $e) {
            return false;
        }

        $data = $this->driver->get($key);

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

        return $data['content'];
    }

    /**
     * Сохраняет контент в кэш
     * @param string $content Контент для сохранения
     */
    private function setCache($content)
    {
        if (!$this->shouldCache()) {
            return;
        }

        // Вытаскиваем заголовки
        $headers = [];
        $headers_list = headers_list();
        if (sizeof($headers_list) > 0) {
            foreach ($headers_list as $header) {
                if (strpos($header, "X-Cache:") === false) // Искл. заголовок кеша
                    $headers[] = $header;
            }
        }

        try {
            $key = $this->generateCacheKey();
        } catch (\Exception $e) {
            return false;
        }

        $date = [
            'headers' => $headers,
            'content' => $content,
            'expires' => time() + $this->cacheTime,
        ];
        return $this->driver->set($key, $date, $this->cacheTime);
    }

    /**
     * Возвращает страницу из кеша или инклуда
     * @return string|null
     */
    public function Open()
    {
        // Проверяем наличие кэша
        if ($cachedContent = $this->getCache()) {
            // Отправляем заголовки
            header("X-Cache: HIT");

            echo $cachedContent;
            exit;
        }

        $fileInclude = $_SERVER["DOCUMENT_ROOT"] . "/index.php.origin";
        if (!file_exists($fileInclude)) {
            throw new \RuntimeException("Original index file not found: " . $fileInclude);
        }

        // Начинаем буферизацию
        ob_start();
        include $fileInclude;
        $content = ob_get_clean();

        // Отправляем заголовки
        header('X-Cache: MISS');

        if (empty($content))
            return ""; // Сразу выходим, чтобы не насиловать систему

        if(!$this->setCache($content))
            return null;

        return $content;
    }
}
