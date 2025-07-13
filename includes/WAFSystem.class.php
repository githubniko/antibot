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
    public $enabled = true;

    public $Config;
    public $Logger;
    public $Profile;
    public $WhiteListIP;
    public $BlackListIP;
    public $UserAgentChecker;
    public $RequestAllow;
    public $RequestBlock;
    public $RequestCaptcha;
    public $Marker;
    public $Template;
    public $IndexBot;
    public $TorChecker;
    public $RefererCaptcha;
    public $RefererAllow;
    public $RefererBlock;
    public $FingerPrint;
    public $GrayList;
    public $HTTPChecker;
    public $MobileChecker;
    public $IFrameChecker;
    public $ASNWhite;
    public $ASNBlock;
    public $ASNCaptcha;


    public function __construct()
    {
        $this->initializeComponents();
    }

    private function initializeComponents()
    {
        $this->Config = Config::getInstance();

        # вкл/выкл защиты
        $this->enabled = $this->Config->init('main', 'enabled', $this->enabled, 'вкл/выкл');
        if (!$this->enabled) return;

        $this->Profile = Profile::getInstance($this->Config);
        $this->Logger = new Logger($this->Config, $this->Profile);

        $this->GrayList = new GrayList($this->Config, $this->Logger);
        $this->Marker = new Marker($this->Config, $this->Profile, $this->Logger);
        $this->Template = new Template($this->Config, $this->Profile, $this->Logger);

        $this->BlackListIP = new BlackListIP($this->Config, $this->Logger);
        $this->WhiteListIP = new WhiteListIP($this->Config, $this->Logger);
        $this->IndexBot = new IndexBot($this->Config, $this->Profile, $this->Logger);
        $this->RequestAllow = new RequestChecker($this->Config, $this->Logger, ['listName' => 'whitelist_uri', 'action' => 'ALLOW']);
        $this->RequestBlock = new RequestChecker($this->Config, $this->Logger, ['listName' => 'blacklist_uri', 'action' => 'BLOCK']);
        $this->RequestCaptcha = new RequestChecker($this->Config, $this->Logger, ['listName' => 'captcha_uri', 'action' => 'CAPTCHA']);
        $this->RefererAllow = new RefererChecker($this->Config, $this->Logger, ['listName' => 'whitelist_referer', 'action' => 'ALLOW']);
        $this->RefererBlock = new RefererChecker($this->Config, $this->Logger, ['listName' => 'blacklist_referer', 'action' => 'BLOCK']);
        $this->RefererCaptcha = new RefererChecker($this->Config, $this->Logger, ['listName' => 'captcha_referer', 'action' => 'CAPTCHA']);
        $this->UserAgentChecker = new UserAgentChecker($this->Config, $this->Logger);
        $this->TorChecker = new TorChecker($this->Config, $this->Logger);
        $this->FingerPrint = new FingerPrint($this->Config, $this->Logger);
        $this->HTTPChecker = new HTTPChecker($this->Config, $this->Logger);
        $this->MobileChecker = new MobileChecker($this->Config, $this->Logger);
        $this->IFrameChecker = new IFrameChecker($this->Config, $this->Logger);
        $this->ASNWhite = new ASNChecker($this->Config, $this->Logger, ['listName' => 'whitelist_asn', 'action' => 'ALLOW']);
        $this->ASNBlock = new ASNChecker($this->Config, $this->Logger, ['listName' => 'blacklist_asn', 'action' => 'BLOCK']);
        $this->ASNCaptcha = new ASNChecker($this->Config, $this->Logger, ['listName' => 'captcha_asn', 'action' => 'CAPTCHA']);
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

        # Проверка куки маркера
        if ($this->Marker->isValid()) {
            return true;
        }

        $this->Logger->log("" . $this->Profile->REQUEST_URI);

        if ($this->HTTPChecker->enabled)
            $this->Logger->log("Protocol: " . $this->Profile->HttpVersion);

        if ($this->RefererAllow->enabled || $this->RefererBlock->enabled || $this->RefererCaptcha->enabled)
            $this->Logger->log("REF: " . $this->Profile->Referer);

        # БОЛЕЕ ТЯЖЕЛЫЕ ПРОВЕРКИ ДОБАВЛЯЮТСЯ В КОНЕЦ
        ##### ALLOW #####

        # Разрешенные IP
        if ($this->WhiteListIP->enabled) {
            if ($this->WhiteListIP->isListed($clientIp)) {
                $this->Logger->log("IP address found in whitelist: $clientIp");
                return true;
            }
        }

        # Разрешенные ASN
        if ($this->ASNWhite->enabled) {
            if ($this->ASNWhite->action == 'ALLOW') {
                if ($this->ASNWhite->Checking($this->Profile->IP)) {
                    $this->Logger->log("ASN allowed");
                    return true;
                }
            }
        }

        # Разрешенные поисковые боты
        if ($this->IndexBot->enabled) {
            if ($this->IndexBot->Checking($clientIp)) {
                $this->Logger->log("Indexing robot");
                return true;
            }
        }

        ##### BLOCK #####
        # ПРАВИЛО НЕ БУДЕТ СРАБАТЫВАТЬ, ЕСЛИ У ПОСЕТИТЕЛЯ УСТАНОВЛЕНА МЕТКА
        # НУЖНО РЕАЛИЗОВАТЬ МЕХАНИЗМ СБРОСА МЕТКИ ДЛЯ КОНКРЕТНОГО ПОСЕТИТЕЛЯ

        # Блокировка IPv6
        if ($this->BlackListIP->enabled) {
            if ($this->BlackListIP->ipv6 == 'BLOCK') {
                if ($this->BlackListIP->isIPv6($this->Profile->IP)) {
                    $this->Logger->log("IPv6 blocked");
                    $this->Template->showBlockPage();
                }
            }
        }

        # Блокировка HTTP протоколов
        if ($this->HTTPChecker->enabled) {
            if ($this->HTTPChecker->action == 'BLOCK') {
                if ($this->HTTPChecker->Checking($this->Profile->HttpVersion)) {
                    $this->Logger->log("Version HTTP blocked");
                    if ($this->HTTPChecker->addBlacklistIP) {
                        $this->BlackListIP->add($clientIp, $this->Profile->HttpVersion);
                    }
                    $this->Template->showBlockPage();
                }
            }
        }

        # Блокировка IP
        if ($this->BlackListIP->enabled) {
            if ($this->BlackListIP->isListed($clientIp)) {
                $this->Logger->log("IP address found on blacklist: $clientIp");
                $this->Template->showBlockPage();
            }
        }

        # Блокировка ASN
        if ($this->ASNBlock->enabled) {
            if ($this->ASNBlock->action == 'BLOCK') {
                if ($this->ASNBlock->Checking($this->Profile->IP)) {
                    $this->Logger->log("ASN blocked");
                    $this->Template->showBlockPage();
                }
            }
        }

        # Блокировка TOR
        if ($this->TorChecker->enabled) {
            if ($this->TorChecker->action == 'BLOCK') {
                if ($this->TorChecker->isTor($this->Profile->IP)) {
                    $this->Logger->log("TOR blocked");
                    $this->Template->showBlockPage();
                }
            }
        }

        # Разрешенные URL
        if ($this->RequestAllow->enabled) {
            if ($this->RequestAllow->action == 'ALLOW') {
                if ($this->RequestAllow->isListed($this->Profile->REQUEST_URI)) {
                    $this->Logger->log("REQUEST_URI allowed");
                    return true;
                }
            }
        }

        # Разрешенные User-Agent
        if ($this->UserAgentChecker->enabled) {
            if ($this->UserAgentChecker->action == 'ALLOW') {
                if ($this->UserAgentChecker->isListed($this->Profile->UserAgent)) {
                    $this->Logger->log("User-Agent allowed");
                    return true;
                }
            }
        }

        # Разрешаенные рефереры
        if ($this->RefererAllow->enabled) {
            # Пропускаем посетителей с Прямыми заходом
            if ($this->RefererAllow->isDirect($this->Profile->Referer)) {
                $this->Logger->log("DIRECT allowed");
                $this->Marker->set();
                return true;
            }
            if ($this->RefererAllow->Checking($this->Profile->Referer)) {
                $this->Logger->log("HTTP_REFERER allowed");
                $this->Marker->set();
                return true;
            }
        }

        # Проверка User-Agent
        if ($this->UserAgentChecker->enabled) {
            // Валидность User_Agent
            if (!$this->UserAgentChecker->isValid($this->Profile->UserAgent)) {
                $this->Template->showBlockPage();
            }
        }

        # Блокировка Рефереров
        if ($this->RefererBlock->enabled) {
            if ($this->RefererBlock->Checking($this->Profile->Referer)) {
                $this->Logger->log("HTTP_REFERER blocked");
                $this->Template->showBlockPage();
            }
        }

        # Блокировка URL
        if ($this->RequestBlock->enabled) {
            if ($this->RequestBlock->action == 'BLOCK') {
                if ($this->RequestBlock->isListed($this->Profile->REQUEST_URI)) {
                    $this->Logger->log("REQUEST_URI blocked");
                    $this->Template->showBlockPage();
                }
            }
        }

        ##### ЗАКРЫВАЮЩИЕ ПРАВИЛА #####

        # Показ капчи для списков
        if ($this->RefererCaptcha->action == 'CAPTCHA') {
            if ($this->RefererCaptcha->Checking($this->Profile->Referer)) {
                $this->Logger->log("REQUEST_URI captcha");
                return false;
            }
        }
        # Пропускаем посетителей с реферером
        if ($this->RefererAllow->isReferer($this->Profile->Referer)) {
            $this->Logger->log("REQUEST_URI allowed");
            $this->Marker->set();
            return true;
        }
        # Блокировка посетителей с реферером
        if ($this->RefererBlock->isReferer($this->Profile->Referer)) {
            $this->Logger->log("REQUEST_URI blocked");
            $this->Template->showBlockPage();
        }

        return false;
    }

    public function isAllowed2()
    {
        $Api = Api::getInstance($this);
        new SysUpdate($this->Config, $this->Logger); // Обновляем систему пока проходит проверка на роботность

        # Блокировка плохих запросов
        if (!$Api->isPost()) {
            $this->Logger->log("Not a POST request");
            $this->BlackListIP->add($this->Profile->IP, 'Not a POST request');
            $Api->endJSON('block');
        }

        $data = $Api->getData();

        # Блокировка, eсли не удалось получить FingerPrint
        if (!isset($data['fingerPrint']) || empty($data['fingerPrint'])) {
            $this->Logger->log("Not FingerPrint");
            $Api->endJSON('block');
        }
        $this->Profile->FingerPrint = $data['fingerPrint']; // дополняем профиль посетителя FP

        # Блокировка, если не удалось получить Request_Uri
        if (!isset($data['location']['pathname']) || !isset($data['location']['search'])) {
            $this->Logger->log("Not Request_Uri");
            $Api->endJSON('block');
        }
        $this->Profile->REQUEST_URI = $data['location']['pathname'] . $data['location']['search'];

        # Запрос по событию Закрыл страницу или вкладку
        if ($data['func'] == 'win-close') {
            $this->Logger->log("Closed the verification page");
            $Api->endJSON(''); // возможно тут нужно добавлять пользователя в черный список
        }

        # Запрос на установку метки
        if ($data['func'] == 'set-marker' && $Api->isHiddenValue()) {
            $this->Logger->log("Successfully passed the captcha");
            $this->Marker->set();
            $Api->endJSON('allow');
        }

        # Вывод в лог значения FP
        $this->Logger->log("FP:  " . $this->Profile->FingerPrint);

        ##### BLOCK #####

        # Блокировка мобильных девайсов
        if ($this->MobileChecker->enabled) {
            if ($this->MobileChecker->action == 'BLOCK') {
                if ($this->MobileChecker->Checking($this->Profile->isMobile, $data['screenWidth'], $data['pixelRatio'])) {
                    $this->Logger->log("Mobile device blocked");
                    $Api->endJSON('block');
                }
            }
        }

        # Блокировка по FingerPrint
        if ($this->FingerPrint->enabled) {
            if ($this->FingerPrint->action == 'BLOCK') {
                if ($this->FingerPrint->Checking($this->Profile->FingerPrint)) {
                    $this->Logger->log("FingerPrint blocked");
                    if ($this->FingerPrint->addBlacklistIP) {
                        $this->BlackListIP->add($this->Profile->IP, 'FP ' . $this->Profile->FingerPrint);
                    }
                    $Api->endJSON('block');
                }
            }
        }

        # Блокировка преходов в iframe
        if ($this->IFrameChecker->enabled) {
            if ($this->IFrameChecker->action == 'BLOCK') {
                if ($this->IFrameChecker->Checking($data['mainFrame'])) {
                    $this->Logger->log("IFrame blocked");
                    $Api->endJSON('block');
                }
            }
        }

        /* 
        * CAPTCHA
        **/

        # Проверка IPv6
        if ($this->BlackListIP->enabled) {
            if ($this->BlackListIP->ipv6 == 'CAPTCHA') {
                if ($this->BlackListIP->isIPv6($this->Profile->IP)) {
                    $this->Logger->log("IPv6 captcha");
                    $Api->endJSON('captcha');
                }
            }
        }

        # Показ капчи для Прямых заходов
        if ($this->RefererCaptcha->enabled) {
            # для посетителей с Прямыми заходом
            if ($this->RefererCaptcha->isDirect($data['referer'])) {
                $this->Logger->log("Show captcha for DIRECT");
                $Api->endJSON('captcha');
            }
            # для посетителей с реферером (будут фильтроваться только прямые заходы)
            if ($this->RefererCaptcha->isReferer($data['referer'])) {
                $this->Logger->log("Show captcha for REFERRER");
                $Api->endJSON('captcha');
            }
            # Показ капчи для списков
            if ($this->RefererCaptcha->action == 'CAPTCHA') {
                if ($this->RefererCaptcha->Checking($data['referer'])) {
                    $this->Logger->log("Show captcha to list REFERRER");
                    $Api->endJSON('captcha');
                }
            }
        }

        # Проверка для мобильных девайсов
        if ($this->MobileChecker->enabled) {
            if ($this->MobileChecker->action == 'CAPTCHA') {
                if ($this->MobileChecker->Checking($this->Profile->isMobile, $data['screenWidth'], $data['pixelRatio'])) {
                    $this->Logger->log("Show captcha for Mobile device");
                    $Api->endJSON('captcha');
                }
            }
        }

        # Проверка для iframe
        if ($this->IFrameChecker->enabled) {
            if ($this->IFrameChecker->action == 'CAPTCHA') {
                if ($this->IFrameChecker->Checking($data['mainFrame'])) {
                    $this->Logger->log("IFrame captcha");
                    $Api->endJSON('captcha');
                }
            }
        }

        # Показ капчи для Протоколов
        if ($this->HTTPChecker->enabled) {
            if ($this->HTTPChecker->action == 'CAPTCHA') {
                if ($this->HTTPChecker->Checking($this->Profile->HttpVersion)) {
                    $this->Logger->log("Show captcha for protocol: " . $this->Profile->HttpVersion);
                    $Api->endJSON('captcha');
                }
            }
        }

        # Проверка ASN
        if ($this->ASNCaptcha->enabled) {
            if ($this->ASNCaptcha->action == 'CAPTCHA') {
                if ($this->ASNCaptcha->Checking($this->Profile->IP)) {
                    $this->Logger->log("Show captcha for ASN");
                    $Api->endJSON('captcha');
                }
            }
        }

        # Блокировка URL
        if ($this->RequestCaptcha->enabled) {
            if ($this->RequestCaptcha->action == 'CAPTCHA') {
                if ($this->RequestCaptcha->isListed($this->Profile->REQUEST_URI)) {
                    $this->Logger->log("Show captcha for REQUEST_URI");
                    $Api->endJSON('captcha');
                }
            }
        }

        # Тор IP
        if ($this->TorChecker->enabled) {
            if ($this->TorChecker->action == 'CAPTCHA') {
                if ($this->TorChecker->isTor($this->Profile->IP)) {
                    $this->Logger->log("IP is TOR captcha");
                    $Api->endJSON('captcha');
                }
            }
        }

        $this->Logger->log("Passed all filters");
        $this->Marker->set();


        $Api->endJSON('allow');
    }
}
