<?php
/*
 * @author EgorNiKO <niko_egor@mail.ru>
 * @repository https://github.com/githubniko/antibot
 *
 * @copyright Copyright (c) 2025, EgorNiKO. All rights reserved.
 * @license MIT License
 */

namespace WAFSystem;

class WAFSystem
{
    public $Config;
    public $Logger;
    public $Profile;
    public $WhiteListIP;
    public $BlackLiskIP;
    public $WhiteListUserAgent;
    public $RequestChecker;
    public $Marker;
    public $Template;
    public $IndexBot;
    public $TorChecker;
    public $RefererChecker;
    public $FingerPrint;
    public $GrayList;
    public $HTTPChecker;

    public function __construct()
    {
        $this->initializeComponents();
    }

    private function initializeComponents()
    {
        $this->Config = Config::getInstance();
        $this->Profile = Profile::getInstance($this->Config);
        $this->Logger = new Logger($this->Config, $this->Profile);


        $this->WhiteListIP = new WhiteListIP($this->Config, $this->Logger);
        $this->BlackLiskIP = new BlackListIP($this->Config, $this->Logger);
        $this->WhiteListUserAgent = new WhiteListUserAgent($this->Config, $this->Logger);
        $this->RequestChecker = new RequestChecker($this->Config, $this->Logger);
        $this->Marker = new Marker($this->Config, $this->Profile, $this->Logger);
        $this->Template = new Template($this->Config, $this->Profile, $this->Logger);
        $this->IndexBot = new IndexBot($this->Config, $this->Profile, $this->Logger);
        $this->TorChecker = new TorChecker($this->Config, $this->Logger);
        $this->RefererChecker = new RefererChecker($this->Config, $this->Logger);
        $this->FingerPrint = new FingerPrint($this->Config, $this->Logger);
        $this->GrayList = new GrayList($this->Config, $this->Logger);
        $this->HTTPChecker = new HTTPChecker($this->Config, $this->Logger);
    }

    public function run()
    {
        try {
            if (!$this->isAllowed()) {
                $this->Template->showCaptcha();
            }
        } catch (\Exception $e) {
            $this->Logger->log("System error: " . $e->getMessage());
            $this->Template->showCaptcha();
        }
    }

    private function isAllowed()
    {
        $clientIp = $this->Profile->IP;
        if (PHP_SAPI === 'cli') { // разрешаем локальный запуск PHP
            $this->Logger->log("Script is run from the command line: ". $_SERVER['PHP_SELF']);
            return true;
        }

        $this->Logger->log("" . $this->Profile->REQUEST_URI);
        $this->Logger->log("REF: " . $this->Profile->Referer);

        if ($this->HTTPChecker->enabled)
            $this->Logger->log("Protocol: " . $this->Profile->HttpVersion);

        // 0. Проверка протокола
        if ($this->HTTPChecker->enabled) {
            if ($this->HTTPChecker->Checking($this->Profile->HttpVersion)) {
                if ($this->HTTPChecker->action == 'ALLOW') {
                    $this->Logger->log("Version HTTP allowed");
                    return true;
                }
            }
        }

        // 1. Проверка URL в белом списке
        if ($this->RequestChecker->isListed($this->Profile->REQUEST_URI)) {
            $this->Logger->log("REQUEST_URI whitelist");
            return true;
        }

        // 2. Проверка IP в черном списке
        if ($this->BlackLiskIP->isListed($clientIp)) {
            $this->Logger->log("IP address found on blacklist: $clientIp");
            $this->Template->showBlockPage();
        }

        // 3. Проверка куки маркера
        if ($this->Marker->isValid()) {
            $this->Logger->log("Tag found");
            return true;
        }

        // 4. Проверка IP в белом списке
        if ($this->WhiteListIP->isListed($clientIp)) {
            $this->Logger->log("IP address found in whitelist: $clientIp");
            return true;
        }

        // Пропускаем посетителей с Прямым заходом
        if ($this->RefererChecker->isDirect($this->Profile->Referer, 'ALLOW')) {
            $this->Logger->log("Direct entry permitted");
            $this->Marker->set();
            return true;
        }

        // Пропускаем посетителей с реферером (будут фильтроваться только прямые заходы)
        if ($this->RefererChecker->isReferer($this->Profile->Referer, 'ALLOW')) {
            $this->Logger->log("HTTP_REFERER allowed");
            $this->Marker->set();
            return true;
        }

        // 5. Проверка User-Agent
        if ($this->Config->get('checks', 'useragent', false)) {
            // Валидность User_Agent
            if (!$this->WhiteListUserAgent->isValid($this->Profile->UserAgent)) {
                return false;
            }

            // Пропускаем исключенные User-Agent
            if ($this->WhiteListUserAgent->isListed($this->Profile->UserAgent)) {
                return true;
            }
        }

        // 7. Проверка поисковых ботов
        if ($this->IndexBot->isIndexbot($clientIp)) {
            $this->Logger->log("Indexing robot");
            return true;
        }

        // 9. Проверка протокола
        if ($this->HTTPChecker->enabled) {
            if ($this->HTTPChecker->Checking($this->Profile->HttpVersion)) {
                if ($this->HTTPChecker->action == 'BLOCK') {
                    if ($this->HTTPChecker->addBlacklistIP) {
                        $this->BlackLiskIP->add($clientIp, $this->Profile->HttpVersion);
                    }
                    $this->Template->showBlockPage();
                }
            }
        }

        return false;
    }

    public function isAllowed2()
    {
        $Api = Api::getInstance($this);
        new SysUpdate($this->Config, $this->Logger); // Обновляем систему

        # Важен приоритет проверки
        if (!$Api->isPost()) {
            $this->Logger->log("Not a POST request");
            $this->BlackLiskIP->add($this->Profile->IP, 'Not a POST request');
            $Api->endJSON('block');
        }

        $data = $Api->getData();

        if (!isset($data['fingerPrint']) || empty($data['fingerPrint'])) {
            $this->Logger->log("Not FingerPrint");
            $Api->endJSON('block');
        }
        $this->Profile->FingerPrint = $data['fingerPrint'];

        # Запрос по событию Закрыл страницу или вкладку
        if ($data['func'] == 'win-close') {
            $this->Logger->log("Closed the verification page");
            $Api->endJSON(''); // возможно тут нужно добавлять пользователя в черный список
        }

        /* 
        * ALLOW
        **/

        # Запрос на установку метки
        if ($data['func'] == 'set-marker') {
            $this->Logger->log("Successfully passed the captcha");
            $this->Marker->set();
            $Api->endJSON('allow');
        }

        /* 
        * BLOCK
        **/

        # Блокировка по FingerPrint
        if ($this->FingerPrint->isFP($this->Profile->FingerPrint)) {
            $this->BlackLiskIP->add($this->Profile->IP, 'FP ' . $this->Profile->FingerPrint);
            $Api->endJSON('block');
        }

        # Проверка для iframe
        if ($this->Config->init('checks', 'iframe', false, 'блокировать если открытие во if-frame') && $data['mainFrame'] != true) {
            $this->Logger->log("Open in frame");
            $Api->endJSON('block');
        }

        /* 
        * BLOCK OR CAPTCHA
        **/

        # Тор IP
        if ($this->TorChecker->enabled) {
            if ($this->TorChecker->isTor($this->Profile->IP)) {
                $this->Logger->log("IP is TOR exit node");
                if ($this->TorChecker->action == 'BLOCK') {
                    $this->BlackLiskIP->add($this->Profile->IP, 'tor');
                    $Api->endJSON('block');
                } elseif ($this->TorChecker->action == 'CAPTCHA') {
                    $Api->endJSON('captcha');
                }
            }
        }
        
        /* 
        * CAPTCHA
        **/

        # Показ капчи для Протоколов
        if ($this->HTTPChecker->enabled) {
            if ($this->HTTPChecker->Checking($this->Profile->HttpVersion)) {
                if ($this->HTTPChecker->action == 'CAPTCHA') {
                    $this->Logger->log("Show captcha for protocol: " . $this->Profile->HttpVersion);
                    $Api->endJSON('captcha');
                }
            }
        }

        if ($this->Config->init('checks', 'ipv6', true, 'капча для IPv6') && filter_var($this->Profile->IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->Logger->log("Show captcha for IPv6");
            $Api->endJSON('captcha');
        }

        # Проверка для мобильных девайсов
        $limitwidth = $this->Config->init('mobile', 'screen_width', 1920, 'px, минимальная ширина экрана. Работает совместно с [checks]->mobile');
        $screenWidth = (float)$data['screenWidth'] * (float)$data['pixelRatio'];
        if ($this->Config->init('checks', 'mobile', true, 'капча для мобильных девайсов') && $screenWidth < $limitwidth) {
            $this->Logger->log("Screen resolution {$screenWidth}px is less than {$limitwidth}px");
            $Api->endJSON('captcha');
        }

        # Показ капчи для Прямых заходов
        if ($this->RefererChecker->isDirect($data['referer'], 'CAPTCHA')) {
            $this->Logger->log("Show captcha for direct transition");
            $Api->endJSON('captcha');
        }

        # Показ капчи для Прямых заходов
        if ($this->RefererChecker->isReferer($data['referer'], 'CAPTCHA')) {
            $this->Logger->log("Show captcha if there is a referrer");
            $Api->endJSON('captcha');
        }

        $this->Logger->log("Passed all filters");
        $this->Marker->set();


        $Api->endJSON('allow');
    }
}
