<?php

namespace WAFSystem;

class HTTPChecker
{
    private $Config;
    private $protocols = [];

    public $action = 'BLOCK';
    public $enabled = false;
    public $addBlacklistIP = false;

    public function __construct(Config $config, Logger $logger)
    {
        $this->Config = $config;

<<<<<<< HEAD
        $this->enabled = $this->Config->init('checks', 'http_checker', $this->enabled, 'проверка версии HTTP-протокола');
=======
        $this->enabled = $this->Config->init('checks', 'http_checker', $this->enabled, '');
>>>>>>> bf153bf80a139e6bad61a33bbf5d57287f4fa76f
        $this->protocols = $this->Config->init('http_checker', 'protocols', ['HTTP/1.0'], 'перечисления протоколов через запятую, HTTP/1.0,HTTP/1.1');
        $this->action = $this->Config->init('http_checker', 'action', $this->action, 'ALLOW - разрешить, CAPTCHA - капча, BLOCK - заблокировать, SKIP - пропустить правило');
        $this->addBlacklistIP = $this->Config->init('http_checker', 'add_blacklist_ip', $this->addBlacklistIP, 'On - добавить ip в черный список');
    }

    public function Checking($protocol)
    {
        if (is_array($this->protocols)) {
            foreach($this->protocols as $value) {
                if(strtoupper($value) == $protocol) {
                    return true;
                }
            }
        } elseif (strtoupper($this->protocols) == $protocol) {
            return true;
        }

        return false;
    }
}
