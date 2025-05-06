<?php

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
    public $CaptchaHandler;
    public $IndexBot;
    public $TorChecker;


    public function __construct()
    {
        $this->initializeComponents();
    }

    private function initializeComponents()
    {
        $this->Config = Config::getInstance();
        $this->Profile = Profile::getInstance();
        $this->Logger = new Logger($this->Config, $this->Profile);

        $this->WhiteListIP = new WhiteListIP($this->Config, $this->Logger);
        $this->BlackLiskIP = new BlackListIP($this->Config, $this->Logger);
        $this->WhiteListUserAgent = new WhiteListUserAgent($this->Config, $this->Logger);
        $this->RequestChecker = new RequestChecker($this->Config, $this->Logger);
        $this->Marker = new Marker($this->Config, $this->Profile, $this->Logger);
        $this->CaptchaHandler = new CaptchaHandler($this->Config, $this->Profile, $this->Logger);
        $this->IndexBot = new IndexBot($this->Config, $this->Profile, $this->Logger);
        $this->TorChecker = new TorChecker($this->Config, $this->Logger);
    }

    public function run()
    {
        try {
            if (!$this->isAllowed()) {
                $this->CaptchaHandler->showCaptcha();
            }
        } catch (\Exception $e) {
            $this->Logger->log("System error: " . $e->getMessage());
            $this->CaptchaHandler->showCaptcha();
        }
    }

    private function isAllowed()
    {
        $clientIp = $this->Profile->IP;

        $this->Logger->log("" . $this->Profile->REQUEST_URI);
        $this->Logger->log("" . $this->Profile->UserAgent);
        $this->Logger->log("REF: " . $this->Profile->Referer);

        // 1. Проверка URL в белом списке
        if ($this->RequestChecker->isListed($this->Profile->REQUEST_URI)) {
            $this->Logger->log("REQUEST_URI whitelist");
            return true;
        }

        // 2. Проверка IP в черном списке
        if ($this->BlackLiskIP->isListed($clientIp)) {
            $this->Logger->log("IP address found on blacklist: $clientIp");
            $this->CaptchaHandler->showBlockPage();
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
        if (
            $this->Config->init('checks', 'direct', 'CAPTCHA', 'ALLOW - разрешить прямые заходы, CAPTCHA - капча для прямого захода, SKIP - пропустить правило') == 'ALLOW'
            && (empty($this->Profile->Referer))
        ) {
            $this->Logger->log("Direct entry permitted");
            $this->Marker->set();
            return true;
        }

        // Пропускаем посетителей с реферером (будут фильтроваться только прямые заходы)
        if (
            $this->Config->init('checks', 'referer', 'ALLOW', 'ALLOW - разрешить при наличии реферера, SKIP - пропустить правило') == 'ALLOW'
            && (!empty($this->Profile->Referer) && !mb_eregi("^http(s*):\/\/" . $this->Profile->Host, $this->Profile->Referer))
        ) {
            $this->Logger->log("HTTP_REFERER allowed");
            $this->Marker->set();
            return true;
        }

        // 5. Проверка User-Agent
        if ($this->Config->get('checks', 'useragent', false)) {
            // Валидность User_Agent
            if (!$this->WhiteListUserAgent->isValid($this->Profile->UserAgent)) {
                $this->BlackLiskIP->add($clientIp, 'Invalid User-Agent');
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
            $this->WhiteListIP->add($clientIp, 'indexbot');
            return true;
        }

        // 8. Проверка Tor
        if ($this->TorChecker->isTor($clientIp)) {
            $this->Logger->log("The IP address is a Tor exit node");
            $this->BlackLiskIP->add($clientIp, 'Tor');
            $this->CaptchaHandler->showBlockPage();
        }

        return false;
    }

    public function isAllowed2()
    {
        $Api = Api::getInstance($this);

        # Важен приоритет проверки
        if (!$Api->isPost()) {
            $this->Logger->log("Not a POST request");
            $this->BlackLiskIP->add($this->Profile->IP, 'Not a POST request');
            $Api->endJSON('block');
        }

        $data = $Api->getData();

        # Запрос по событию Закрыл страницу или вкладку
        if ($data['func'] == 'win-close') {
            $this->Logger->log("Closed the verification page");
            $Api->endJSON(''); // возможно тут нужно добавлять пользователя в черный список
        }

        # Запрос на установку метки
        if ($data['func'] == 'set-marker') {
            $this->Logger->log("Successfully passed the captcha");
            $this->Marker->set();
            $Api->removeCSRF();
            $Api->endJSON('allow');
        }

        if ($this->Config->init('checks', 'ipv6', true, 'капча для IPv6') && filter_var($this->Profile->IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->Logger->log("IPv6 address");
            $Api->endJSON('captcha');
        }

        # Проверка для мобильных девайсов
        $screen_width = $this->Config->init('mobile', 'screen_width', 1920, 'px, минимальная ширина экрана. Работает совместно с [checks]->mobile');
        if ($this->Config->init('checks', 'mobile', true, 'капча для мобильных девайсов') && (int)$data['screenWidth'] < $screen_width) {
            $this->Logger->log("Screen resolution is less than {$screen_width}px");
            $Api->endJSON('captcha');
        }

        # Проверка для iframe
        if ($this->Config->init('checks', 'iframe', false, 'блокировать если открытие во if-frame') && $data['mainFrame'] != true) {
            $this->Logger->log("Open in frame");
            $this->BlackLiskIP->add($this->Profile->IP, 'iframe');
            $Api->endJSON('block');
        }

        # Показ капчи для Прямых заходов
        if ($this->Config->init('checks', 'direct', 'CAPTCHA', 'ALLOW - разрешить прямые заходы, CAPTCHA - капча для прямого захода, SKIP - пропустить правило') == 'CAPTCHA' && (empty($data['referer']) || mb_eregi("^http(s*):\/\/" . $this->Profile->Host, $data['referer']))) {
            $this->Logger->log("Direct transition");
            $Api->endJSON('captcha');
        }

        $this->Logger->log("Passed all filters");
        $this->Marker->set();


        $Api->endJSON('allow');
    }
}
