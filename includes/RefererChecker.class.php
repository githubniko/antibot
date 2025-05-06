<?php

namespace WAFSystem;

class RefererChecker
{
    private $Config;

    public function __construct(Config $config, Logger $logger)
    {
        $this->Config = $config;

        $config->init('checks', 'direct', 'CAPTCHA', 'ALLOW - разрешить прямые заходы, CAPTCHA - капча для прямого захода, SKIP - пропустить правило');
        $config->init('checks', 'referer', 'ALLOW', 'ALLOW - разрешить при наличии реферера, SKIP - пропустить правило');
    }

    public function isDirect($referer, $action = 'ALLOW')
    {
        return $this->Config->get('checks', 'direct') == $action
            && (empty($referer) || mb_eregi("^http(s*):\/\/" . $this->Config->HTTP_HOST, $referer));
    }

    public function isReferer($referer, $action = 'ALLOW')
    {
        return
            $this->Config->get('checks', 'referer') == $action
            && (!empty($referer) && !mb_eregi("^http(s*):\/\/" . $this->Config->HTTP_HOST, $referer));
    }
}
