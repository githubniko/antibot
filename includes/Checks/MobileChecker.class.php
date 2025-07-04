<?php

namespace WAFSystem;

class MobileChecker
{
    public $action = 'SKIP';
    public $enabled = false;

    private $Logger;
    private $modulName = 'mobile_checker';
    private $limitWidth = 1920;

    public function __construct(Config $config, Logger $logger)
    {
        $this->Logger = $logger;
        $this->enabled = $config->init($this->modulName, 'enabled', $this->enabled, 'проверка мобильных девайсов');
        $this->action = $config->init($this->modulName, 'action', $this->action, 'CAPTCHA - капча, BLOCK - заблокировать, SKIP - ничего не делать');
        $this->limitWidth = $config->init($this->modulName, 'screen_width', $this->limitWidth, 'px, минимальная ширина экрана');
    }

    public function Checking($isMobile, $screenWidth, $pixelRatio)
    {
        if ($isMobile === null) {
            $screenWidth = (float)$screenWidth * (float)$pixelRatio;
            if ($screenWidth < $this->limitWidth) {
                $this->Logger->log("Screen resolution {$screenWidth}px is less than {$this->limitWidth}px");
                return true;
            }
        }

        return $isMobile;
    }
}
