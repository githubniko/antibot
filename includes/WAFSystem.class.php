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
    public $UserAgentChecker;
    public $RequestChecker;
    public $Marker;
    public $Template;
    public $IndexBot;
    public $TorChecker;
    public $RefererChecker;
    public $FingerPrint;
    public $GrayList;
    public $HTTPChecker;
    public $MobileChecker;
    public $IFrameChecker;

    public function __construct()
    {
        $this->initializeComponents();
    }

    private function initializeComponents()
    {
        $this->Config = Config::getInstance();
        $this->Profile = Profile::getInstance($this->Config);
        $this->Logger = new Logger($this->Config, $this->Profile);

        $this->GrayList = new GrayList($this->Config, $this->Logger);
        $this->Marker = new Marker($this->Config, $this->Profile, $this->Logger);
        $this->Template = new Template($this->Config, $this->Profile, $this->Logger);

        $this->BlackLiskIP = new BlackListIP($this->Config, $this->Logger);
        $this->WhiteListIP = new WhiteListIP($this->Config, $this->Logger);
        $this->IndexBot = new IndexBot($this->Config, $this->Profile, $this->Logger);
        $this->RequestChecker = new RequestChecker($this->Config, $this->Logger);
        $this->RefererChecker = new RefererChecker($this->Config, $this->Logger);
        $this->UserAgentChecker = new UserAgentChecker($this->Config, $this->Logger);
        $this->TorChecker = new TorChecker($this->Config, $this->Logger);
        $this->FingerPrint = new FingerPrint($this->Config, $this->Logger);
        $this->HTTPChecker = new HTTPChecker($this->Config, $this->Logger);
        $this->MobileChecker = new MobileChecker($this->Config, $this->Logger);
        $this->IFrameChecker = new IFrameChecker($this->Config, $this->Logger);
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
            $this->Logger->log("Script is run from the command line: " . $_SERVER['PHP_SELF']);
            return true;
        }

        $this->Logger->log("" . $this->Profile->REQUEST_URI);

        if ($this->HTTPChecker->enabled)
            $this->Logger->log("Protocol: " . $this->Profile->HttpVersion);


        // 1. Проверка URL в белом списке
        if ($this->RequestChecker->enabled) {
            if ($this->RequestChecker->isListed($this->Profile->REQUEST_URI)) {
                if ($this->RequestChecker->action == 'ALLOW') {
                    $this->Logger->log("REQUEST_URI allowed");
                    return true;
                } else {
                    $this->Logger->log("REQUEST_URI skipped");
                }
            }
        }

        // 2. Проверка IP в черном списке
        if ($this->BlackLiskIP->enabled) {
            if ($this->BlackLiskIP->isListed($clientIp)) {
                $this->Logger->log("IP address found on blacklist: $clientIp");
                $this->Template->showBlockPage();
            }
        }

        // 3. Проверка куки маркера
        if ($this->Marker->isValid()) {
            $this->Logger->log("Tag found");
            return true;
        }

        // 4. Проверка IP в белом списке
        if ($this->WhiteListIP->enabled) {
            if ($this->WhiteListIP->isListed($clientIp)) {
                $this->Logger->log("IP address found in whitelist: $clientIp");
                return true;
            }
        }

        // Пропускаем посетителей с Прямым заходом
        if ($this->RefererChecker->enabled) {
            $this->Logger->log("REF: " . $this->Profile->Referer);
            
            if ($this->RefererChecker->isDirect($this->Profile->Referer, 'ALLOW')) {
                $this->Logger->log("DIRECT allowed");
                $this->Marker->set();
                return true;
            }
            // Пропускаем посетителей с реферером (будут фильтроваться только прямые заходы)
            if ($this->RefererChecker->isReferer($this->Profile->Referer, 'ALLOW')) {
                $this->Logger->log("HTTP_REFERER allowed");
                $this->Marker->set();
                return true;
            }
        }

        // 5. Проверка User-Agent
        if ($this->UserAgentChecker->enabled) {
            // Валидность User_Agent
            if (!$this->UserAgentChecker->isValid($this->Profile->UserAgent)) {
                return false;
            }

            // Пропускаем исключенные User-Agent
            if ($this->UserAgentChecker->isListed($this->Profile->UserAgent)) {
                if ($this->UserAgentChecker->action == 'ALLOW') {
                    $this->Logger->log("User-Agent allowed");
                    return true;
                }
            }
        }

        // 7. Проверка поисковых ботов
        if ($this->IndexBot->enabled) {
            if ($this->IndexBot->Checking($clientIp)) {
                $this->Logger->log("Indexing robot");
                return true;
            }
        }

        // 9. Проверка протокола
        if ($this->HTTPChecker->enabled) {
            if ($this->HTTPChecker->Checking($this->Profile->HttpVersion)) {
                if ($this->HTTPChecker->action == 'BLOCK') {
                    $this->Logger->log("Version HTTP blocked");
                    if ($this->HTTPChecker->addBlacklistIP) {
                        $this->BlackLiskIP->add($clientIp, $this->Profile->HttpVersion);
                    }
                    $this->Template->showBlockPage();
                } else if($this->HTTPChecker->action == 'SKIP') {
                    $this->Logger->log("Version HTTP skipped");
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

        if ($this->FingerPrint->enabled)
            $this->Logger->log("FP:  " . $this->Profile->FingerPrint);

        # Запрос по событию Закрыл страницу или вкладку
        if ($data['func'] == 'win-close') {
            $this->Logger->log("Closed the verification page");
            $Api->endJSON(''); // возможно тут нужно добавлять пользователя в черный список
        }

        /* 
        * ALLOW
        **/
        # Запрос на установку метки
        if ($data['func'] == 'set-marker' && $Api->isHiddenValue()) {
            $this->Logger->log("Successfully passed the captcha");
            $this->Marker->set();
            $Api->endJSON('allow');
        }

        /* 
        * BLOCK
        **/

        # Блокировка по FingerPrint
        if ($this->FingerPrint->enabled) {
            if ($this->FingerPrint->Checking($this->Profile->FingerPrint)) {
                if ($this->FingerPrint->action == 'BLOCK') {
                    $this->Logger->log("FingerPrint blocked");
                    if ($this->FingerPrint->addBlacklistIP) {
                        $this->BlackLiskIP->add($this->Profile->IP, 'FP ' . $this->Profile->FingerPrint);
                    }
                    $Api->endJSON('block');
                } else {
                    $this->Logger->log("FingerPrint skipped");
                }
            }
        }

        # Проверка для iframe
        if ($this->IFrameChecker->enabled) {
            if ($this->IFrameChecker->Checking($data['mainFrame'])) {
                if ($this->IFrameChecker->action == 'BLOCK') {
                    $this->Logger->log("IFrame blocked");
                    $Api->endJSON('block');
                } 
                elseif ($this->IFrameChecker->action == 'CAPTCHA') {
                    $this->Logger->log("IFrame captcha");
                    $Api->endJSON('captcha');
                }
                else {
                    $this->Logger->log("IFrame skipped");
                }
            }
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

        if ($this->BlackLiskIP->enabled) {
            if ($this->BlackLiskIP->isIPv6($this->Profile->IP)) {
                if ($this->BlackLiskIP->ipv6 == 'BLOCK') {
                    $this->Logger->log("IPv6 blocked");
                    $Api->endJSON('block');
                } elseif ($this->BlackLiskIP->ipv6 == 'CAPTCHA') {
                    $this->Logger->log("IPv6 captcha");
                    $Api->endJSON('captcha');
                } else {
                    $this->Logger->log("IPv6 skipped");
                }
            }
        }

        # Проверка для мобильных девайсов
        if ($this->MobileChecker->enabled) {
            if ($this->MobileChecker->Checking($this->Profile->isMobile, $data['screenWidth'], $data['pixelRatio'])) {
                if ($this->MobileChecker->action == 'CAPTCHA') {
                    $this->Logger->log("Show captcha for Mobile device");
                    $Api->endJSON('captcha');
                } elseif ($this->MobileChecker->action == 'BLOCK') {
                    $this->Logger->log("Mobile device blocked");
                    $Api->endJSON('block');
                } elseif ($this->MobileChecker->action == 'SKIP') {
                    $this->Logger->log("Mobile device skipped");
                }
            }
        }

        # Показ капчи для Прямых заходов
        if ($this->RefererChecker->enabled) {
            if ($this->RefererChecker->isDirect($data['referer'], 'CAPTCHA')) {
                $this->Logger->log("Show captcha for DIRECT");
                $Api->endJSON('captcha');
            }
            # Показ капчи для Прямых заходов
            if ($this->RefererChecker->isReferer($data['referer'], 'CAPTCHA')) {
                $this->Logger->log("Show captcha if there is a REFERRER");
                $Api->endJSON('captcha');
            }
        }

        $this->Logger->log("Passed all filters");
        $this->Marker->set();


        $Api->endJSON('allow');
    }
}
