<?php

namespace WAFSystem;

// ... Реализация работы с капчей
class Template
{
    private $Config;
    private $Profile;
    private $Logger;
    private $metrika = '101475381';

    public function __construct(Config $config, Profile $profile, Logger $logger)
    {
        $this->Config = $config;
        $this->Profile = $profile;
        $this->Logger = $logger;

        $config->init('main', 'header404', false, 'отдает на заглушку 404 заголовок');
        $config->init('main', 'metrika', $this->metrika, 'Код Яндекс Метрики. Можете установить свой код или оставить текущий для сбора данных о ботах нашими специалистами. Пустая строка отключает показ метрики');
    }

    # Функция для вывода страницы проверки и ввода капчи
    function showCaptcha()
    {
        if ($this->Config->get('main', 'header404')) {
            header("HTTP/1.0 404 Not Found");
        }
        header('X-Robots-Tag: noindex');
        header('Pragma: no-cache');
        header('Expires: Thu, 18 Aug 1994 05:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        $this->Logger->log("Displaying the verification page");
        require $this->Config->BasePath . "templates/template.inc.php";
        exit;
    }

    # Функция для вывода страницы блокировки
    function showBlockPage()
    {
        header("HTTP/1.0 403 Forbidden");
        header('X-Robots-Tag: noindex');
        header('Pragma: no-cache');
        header('Expires: Thu, 18 Aug 1994 05:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        $this->Logger->log("Display the blocking page");
        require $this->Config->BasePath . "templates/template_block.inc.php";
        exit;
    }
}
