<?php

namespace WAFSystem;

class RefererChecker
{
    public $enabled = true;
    public $direct = 'CAPTCHA';
    public $referer = 'ALLOW';

    private $modulName = 'referer_checker';
    private $HTTP_HOST = '';


    public function __construct(Config $config, Logger $logger)
    {
        $this->enabled = $config->init($this->modulName, 'enabled', $this->enabled);
        $this->direct = $config->init($this->modulName, 'direct', 'CAPTCHA', 'ALLOW - разрешить прямые заходы, CAPTCHA - капча, SKIP - пропустить правило');
        $this->referer = $config->init($this->modulName, 'referer', 'ALLOW', 'ALLOW - разрешить при наличии реферера, CAPTCHA - капча, SKIP - пропустить правило');
        $this->HTTP_HOST = $config->HTTP_HOST;
    }

    public function isDirect($referer, $action = 'ALLOW')
    {
        return $this->direct == $action
            && (empty($referer) || mb_eregi("^http(s*):\/\/" . $this->HTTP_HOST, $referer));
    }

    public function isReferer($referer, $action = 'ALLOW')
    {
        return
            $this->referer == $action
            && (!empty($referer) && !mb_eregi("^http(s*):\/\/" . $this->HTTP_HOST, $referer));
    }
}
