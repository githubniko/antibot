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
    public $BlackListIP;
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
    public $ASNChecker;

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

        $this->BlackListIP = new BlackListIP($this->Config, $this->Logger);
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
        $this->ASNChecker = new ASNChecker($this->Config, $this->Logger);
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

        # БОЛЕЕ ТЯЖЕЛЫЕ ПРОВЕРКИ ДОБАВЛЯЮТСЯ В КОНЕЦ
        ##### ALLOW #####

        # Проверка куки маркера
        if ($this->Marker->isValid()) {
            $this->Logger->log("Tag found");
            return true;
        }

        # Разрешенные IP
        if ($this->WhiteListIP->enabled) {
            if ($this->WhiteListIP->isListed($clientIp)) {
                $this->Logger->log("IP address found in whitelist: $clientIp");
                return true;
            }
        }
        
        # Разрешенные URL
        if ($this->RequestChecker->enabled) {
            if ($this->RequestChecker->action == 'ALLOW') {
                if ($this->RequestChecker->isListed($this->Profile->REQUEST_URI)) {
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
        
        if ($this->RefererChecker->enabled) {
            $this->Logger->log("REF: " . $this->Profile->Referer);
            # Пропускаем посетителей с Прямыми заходом
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

        # Разрешенные поисковые боты
        if ($this->IndexBot->enabled) {
            if ($this->IndexBot->Checking($clientIp)) {
                $this->Logger->log("Indexing robot");
                return true;
            }
        }

        ##### BLOCK #####

        # Блокировка IPv6
        if ($this->BlackListIP->enabled) {
            if ($this->BlackListIP->ipv6 == 'BLOCK') {
                if ($this->BlackListIP->isIPv6($this->Profile->IP)) {
                    $this->Logger->log("IPv6 blocked");
                    $this->Template->showBlockPage();
                }
            }
        }

        # Блокировка IP
        # ПРАВИЛО НЕ БУДЕТ СРАБАТЫВАТЬ, ЕСЛИ У ПОСЕТИТЕЛЯ УСТАНОВЛЕНА МЕТКА
        # НУЖНО РЕАЛИЗОВАТЬ МЕХАНИЗМ СБРОСА МЕТКИ ДЛЯ КОНКРЕТНОГО ПОСЕТИТЕЛЯ
        if ($this->BlackListIP->enabled) {
            if ($this->BlackListIP->isListed($clientIp)) {
                $this->Logger->log("IP address found on blacklist: $clientIp");
                $this->Template->showBlockPage();
            }
        }

        # Проверка User-Agent
        if ($this->UserAgentChecker->enabled) {
            // Валидность User_Agent
            if (!$this->UserAgentChecker->isValid($this->Profile->UserAgent)) {
                $this->Template->showBlockPage();
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

        # Блокировка ASN
        if ($this->ASNChecker->enabled) {
            if ($this->ASNChecker->action == 'BLOCK') {
                if ($this->ASNChecker->Checking($this->Profile->IP)) {
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

        # Вывод в лог значения FP
        if ($this->FingerPrint->enabled)
            $this->Logger->log("FP:  " . $this->Profile->FingerPrint);

        # Запрос по событию Закрыл страницу или вкладку
        if ($data['func'] == 'win-close') {
            $this->Logger->log("Closed the verification page");
            $Api->endJSON(''); // возможно тут нужно добавлять пользователя в черный список
        }

        ##### ALLOW #####

        # Запрос на установку метки
        if ($data['func'] == 'set-marker' && $Api->isHiddenValue()) {
            $this->Logger->log("Successfully passed the captcha");
            $this->Marker->set();
            $Api->endJSON('allow');
        }

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
        if ($this->ASNChecker->enabled) {
            if ($this->ASNChecker->action == 'CAPTCHA') {
                if ($this->ASNChecker->Checking($this->Profile->IP)) {
                    $this->Logger->log("Show captcha for ASN");
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
