<?php

namespace WAFSystem;

include __DIR__ . '/autoload.php';

class WAFSystem
{
    private $Config;
    private $Logger;
    private $Profile;
    private $IpWhitelist;
    private $IpBlacklist;
    private $UserAgentChecker;
    private $RequestChecker;
    private $Marker;
    private $CaptchaHandler;
    private $IndexBot;
    private $TorChecker;


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
            return true;
        }

        // 4. Проверка IP в белом списке
        if ($this->IpWhitelist->isListed($clientIp)) {
            $this->Logger->log("IP address found in whitelist: $clientIp");
            return true;
        }

        // 5. Проверка User-Agent
        if ($this->Config->get('main', 'useragent_check', false)) {
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
        if ($this->Config->get('main', 'tor_check') && $this->TorChecker->isTor($clientIp)) {
            $this->Logger->log("The IP address is a Tor exit node");
			$this->IpBlacklist->add($clientIp, 'Tor');
			$this->CaptchaHandler->showBlockPage();
        }

        return false;
    }
}
