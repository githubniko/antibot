<?php

namespace WAFSystem;

class WAFSystem
{
    public $Config;
    public $Logger;
    public $Profile;
    public $IpWhitelist;
    public $IpBlacklist;
    public $UserAgentChecker;
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

        $this->IpWhitelist = new IPWhitelist($this->Config, $this->Logger);
        $this->IpBlacklist = new IPBlacklist($this->Config, $this->Logger);
        $this->UserAgentChecker = new UserAgentChecker($this->Config, $this->Logger);
        $this->RequestChecker = new RequestChecker($this->Config, $this->Logger);
        $this->Marker = new Marker($this->Config, $this->Profile, $this->Logger);
        $this->CaptchaHandler = new CaptchaHandler($this->Config, $this->Profile, $this->Logger);
        $this->IndexBot = new IndexBot($this->Config, $this->Logger);
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
        $clientIp = $this->Profile->Ip;
        $userAgent = $this->Profile->UserAgent;

        $this->Logger->log("" . mb_substr($_SERVER['REQUEST_URI'], 0, 255));
        $this->Logger->log("" . mb_substr($userAgent, 0, 255));
        $this->Logger->log("REF: " . mb_substr($_SERVER['HTTP_REFERER'], 0, 255));

        // 1. Проверка URL в белом списке
        if ($this->RequestChecker->isWhitelistedUrl($_SERVER['REQUEST_URI'])) {
            return true;
        }

        // 2. Проверка IP в черном списке
        if ($this->IpBlacklist->isListed($clientIp)) {
            $this->Logger->log("IP address found on blacklist: $clientIp");
            $this->CaptchaHandler->showBlockPage();
        }

        // 3. Проверка куки маркера
        if ($this->Marker->isValid()) {
            $this->Logger->log("Tag found");
            return true;
        }

        // 4. Проверка IP в белом списке
        if ($this->IpWhitelist->isListed($clientIp)) {
            $this->Logger->log("IP address found in whitelist: $clientIp");
            return true;
        }

        // Пропускаем посетителей с Прямым заходом
        if ($this->Config->get('checks', 'direct', false) == 'ALLOW' && (empty($_SERVER['HTTP_REFERER']))) {
            $this->Logger->log("Direct entry permitted");
            $this->Marker->set();
            return true;
        }

        // Пропускаем посетителей с реферером (будут фильтроваться только прямые заходы)
        if ($this->Config->get('checks', 'referer', false) == 'ALLOW' && (!empty($_SERVER['HTTP_REFERER']) && !mb_eregi("^http(s*):\/\/".$_SERVER['HTTP_HOST'] , $_SERVER['HTTP_REFERER']))) {
            $this->Logger->log("HTTP_REFERER allowed");
            $this->Marker->set();
            return true;
        }

        // 5. Проверка User-Agent
        if ($this->Config->get('checks', 'useragent', false)) {
            // Валидность User_Agent
            if (!$this->UserAgentChecker->isValid($userAgent)) {
                $this->IpBlacklist->add($clientIp, 'Invalid User-Agent');
                return false;
            }

            // Проверка исключений User-Agent
            if ($this->UserAgentChecker->isExcludedBot($userAgent)) {
                return true;
            }

            // Проверка разрешенных шаблонов User-Agent
            if ($this->UserAgentChecker->isAllowed($userAgent)) {
                return true;
            }
        }

        // 7. Проверка поисковых ботов
        if ($this->IndexBot->isIndexbot($clientIp)) {
            $this->Logger->log("Indexing robot");
            $this->IpWhitelist->add($clientIp, 'indexbot');
            return true;
        }

        // 8. Проверка Tor
        if ($this->Config->get('checks', 'tor') && $this->TorChecker->isTor($clientIp)) {
            $this->Logger->log("The IP address is a Tor exit node");
            $this->IpBlacklist->add($clientIp, 'Tor');
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
            $this->IpBlacklist->add($this->Profile->Ip, 'Not a POST request');
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

        if ($this->Config->get('checks', 'ipv6', false) && IPList::isIPv6($this->Profile->Ip)) {
            $this->Logger->log("IPv6 address");
            $Api->endJSON('captcha');
        }

        # Проверка для мобильных девайсов
        $screen_width = !$this->Config->get('main', 'screen_width', false) ? 1920 : $this->Config->get('main', 'screen_width');
        if ($this->Config->get('checks', 'mobile', false) && $data['screenWidth'] < $screen_width) {
            $this->Logger->log("Screen resolution is less than {$screen_width}px");
            $Api->endJSON('captcha');
        }

        # Проверка для iframe
        if ($this->Config->get('checks', 'iframe', false) && $data['mainFrame'] != true) {
            $this->Logger->log("Open in frame");
            $this->IpBlacklist->add($this->Profile->Ip, 'iframe');
            $Api->endJSON('block');
        }

        # Показ капчи для Прямых заходов
        if ($this->Config->get('checks', 'direct', false) == 'CAPTCHA' && (empty($data['referer']) || mb_eregi("^http(s*):\/\/" . $this->Profile->Host, $data['referer']))) {
            $this->Logger->log("Direct transition");
            $Api->endJSON('captcha');
        }

        $this->Logger->log("Passed all filters");
        $this->Marker->set();


        $Api->endJSON('allow');
    }
}
