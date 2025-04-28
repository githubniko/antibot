<?php

namespace WAFSystem;

// ... Реализация работы с капчей
class CaptchaHandler
{
    private $Config;
    private $Profile;
    private $Logger;


    public function __construct(Config $config, Profile $profile, Logger $logger) {
        $this->Config = $config;
        $this->Profile = $profile;
        $this->Logger = $logger;
        
    }

    # Функция для вывода страницы проверки и ввода капчи
    function showCaptcha()
    {
        if ($this->Config->get('main', 'header404')) {
            header("HTTP/1.0 404 Not Found");
        }
        
        $this->Logger->log("Displaying the verification page");
        require $this->Config->BasePath . "templates/template.inc.php";
        exit;
    }

    # Функция для вывода страницы блокировки
    function showBlockPage()
    {
        $this->Logger->log("Display the blocking page");
        require $this->Config->BasePath . "templates/template_block.inc.php";
        exit;
    }
}
