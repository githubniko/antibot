<?php

namespace WAFSystem;

class IFrameChecker
{
    public $action = 'SKIP';
    public $enabled = false;
    public $header_block = false;

    private $modulName = 'iframe_checker';

    public function __construct(Config $config, Logger $logger)
    {
        $this->enabled = $config->init($this->modulName, 'enabled', $this->enabled, 'открытие во if-frame');
        $this->action = $config->init($this->modulName, 'action', $this->action, 'CAPTCHA - капча, BLOCK - заблокировать, SKIP - ничего не делать');
        $this->header_block = $config->init($this->modulName, 'block_header', $this->header_block, 'блокировать через header: X-Frame-Options или Content-Security-Policy');
    }

    public function Checking($iframe)
    {
        return !$iframe;
    }

    public function HeaderBlock()
    {
        if (!$this->enabled)
            return;

        if (!$this->header_block)
            return;

        if ($this->action != 'BLOCK')
            return;

        header("X-Frame-Options: DENY");
        header("Content-Security-Policy: frame-ancestors 'none'");
    }
}
