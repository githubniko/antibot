<?
function verifyTurnstileToken($secretKey, $token, $remoteIp = '')
{
    if ($secretKey === '' || $token === '') {
        return false;
    }

    $payload = [
        'secret' => $secretKey,
        'response' => $token
    ];

    if (!empty($remoteIp)) {
        $payload['remoteip'] = $remoteIp;
    }

    $postData = http_build_query($payload);
    $responseBody = false;

    if (function_exists('curl_init')) {
        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);
        $responseBody = curl_exec($ch);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $postData,
                'timeout' => 8
            ]
        ]);
        $responseBody = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
    }

    if ($responseBody === false) {
        return false;
    }

    $decoded = json_decode($responseBody, true);
    return is_array($decoded) && isset($decoded['success']) && $decoded['success'] === true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sendJson = function ($status, $func = '', $extra = []) {
        header('Content-type: application/json; charset=utf-8');
        $res = [
            'func' => $func,
            'status' => $status,
            'csrf_token' => isset($_REQUEST['csrf']) ? (string)$_REQUEST['csrf'] : ''
        ];
        if (!empty($extra)) {
            $res = array_merge($res, $extra);
        }
        echo json_encode($res);
        exit;
    };

    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $antiBot->Logger->log("Turnstile JSON-data is empty");
        $sendJson('fail', '', ['message' => 'invalid_json']);
    }

    $func = isset($data['func']) ? (string)$data['func'] : '';
    $hiddenOk = isset($_SESSION['rndname']) && $_SESSION['rndname'] === true;

    if ($func === $antiBot->Marker->getNameMarker() && $hiddenOk) {
        $turnstileToken = '';
        if (isset($data['turnstile_token'])) {
            $turnstileToken = trim((string)$data['turnstile_token']);
        } elseif (isset($data['turnstileToken'])) {
            $turnstileToken = trim((string)$data['turnstileToken']);
        } elseif (isset($data['cf-turnstile-response'])) {
            $turnstileToken = trim((string)$data['cf-turnstile-response']);
        }

        $turnstileSecretKey = trim((string)$antiBot->Config->get('turnstile', 'secret_key', ''));
        if (!verifyTurnstileToken($turnstileSecretKey, $turnstileToken, $antiBot->Profile->IP)) {
            $antiBot->Logger->log("Turnstile server-side verification failed");
            $sendJson('fail', $func, ['message' => 'turnstile_verification_failed']);
        }

        $antiBot->Logger->log("Successfully passed the captcha");
        unset($_SESSION['rndname']);
        $antiBot->Marker->set();
        $sendJson('allow', $func);
    }

    $sendJson('fail', $func, ["message" => "`" . $func . "` not found"]);
}

$nonce = \Utility\GenerateRandomName::genKey(17);
$verifyFuncName = \Utility\GenerateRandomName::genFuncName();
$turnstileLoadFunc = \Utility\GenerateRandomName::genFuncName();
$turnstileContainerId = Utility\GenerateRandomName::genFuncName(4, 6);

$langMap = [
    "ru" => [
        "title" => "Проверяем ваш Браузер...",
        "checking_human" => "Подтвердите, что вы человек",
    ],
    "en" => [
        "title" => "Checking your Browser...",
        "checking_human" => "Confirm you're human",
    ],
    "zh" => [
        "title" => "正在检查您的浏览器...",
        "checking_human" => "请确认您是人类",
    ],
];

$turnstileSiteKey = $antiBot->Config->get('turnstile', 'site_key', '');

$language = isset($antiBot->Profile->Language) ? $antiBot->Profile->Language : "en";
$language = isset($langMap[$language]) ? $language : "en";
?><html lang="<?php echo $antiBot->Profile->LangAttr ?>" dir="ltr">

<head>
    <meta http-equiv="x-ua-compatible" content="IE=Edge,chrome=1">
    <meta http-equiv="content-security-policy"
        content="default-src 'none'; script-src 'nonce-<?php echo $nonce; ?>' 'unsafe-eval' https://challenges.cloudflare.com; script-src-attr 'none'; worker-src blob:; style-src 'unsafe-inline'; img-src 'self'; connect-src 'self' https://challenges.cloudflare.com; frame-src 'self' blob: https://challenges.cloudflare.com; child-src 'self' blob: https://challenges.cloudflare.com; form-action 'none'; base-uri 'self'">
    <title><?php echo $langMap[$language]['title'] ?></title>
    <meta name="robots" content="noindex,nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style>
        body {
            margin: 0;
            padding: 16px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .turnstile-wrapper {
            width: 100%;
            max-width: 340px;
        }

        .turnstile-label {
            display: block;
            margin-bottom: 12px;
        }

        @media (prefers-color-scheme: dark) {
            body {
                color: #fff;
            }
        }

        /* Стабильная высота документа для расчёта scrollHeight (iframe подстраивается из JS). */
        html {
            overflow: visible;
        }

        body {
            overflow: visible;
            min-height: min-content;
        }
    </style>
    <script
        src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=<?php echo $turnstileLoadFunc ?>"
        defer nonce="<?php echo $nonce ?>"></script>
</head>

<body>
    <div class="turnstile-wrapper">
        <span class="turnstile-label"><?php echo $langMap[$language]['checking_human'] ?></span>
        <div id="<?php echo $turnstileContainerId ?>"></div>
    </div>
    <script type="text/javascript" nonce="<?php echo $nonce ?>">
        let CSRF = <?php echo json_encode(isset($_REQUEST["csrf"]) ? $_REQUEST["csrf"] : "", JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const HTTP_ANTIBOT_PATH = <?php echo json_encode($antiBot->Config->ANTIBOT_PATH, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const TURNSTILE_SITEKEY = <?php echo json_encode($turnstileSiteKey, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const TURNSTILE_LANGUAGE = <?php echo json_encode($language, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const TURNSTILE_MARKER = <?php echo json_encode($antiBot->Marker->getNameMarker(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        let turnstileWidgetId = null;

        function syncParentCaptchaIframeHeight() {
            if (!window.frameElement) {
                return;
            }
            var root = document.documentElement;
            var body = document.body;
            var h = Math.max(
                root.scrollHeight,
                root.offsetHeight,
                body ? body.scrollHeight : 0,
                body ? body.offsetHeight : 0
            );
            window.frameElement.style.height = h + 'px';
        }

        function attachParentIframeHeightSync(extraRoot) {
            syncParentCaptchaIframeHeight();
            window.addEventListener('resize', syncParentCaptchaIframeHeight);
            if (typeof ResizeObserver !== 'undefined') {
                var ro = new ResizeObserver(syncParentCaptchaIframeHeight);
                ro.observe(document.documentElement);
                if (document.body) {
                    ro.observe(document.body);
                }
                if (extraRoot) {
                    ro.observe(extraRoot);
                }
            }
            requestAnimationFrame(function() {
                requestAnimationFrame(syncParentCaptchaIframeHeight);
            });
        }

        function resetTurnstileWidget() {
            if (typeof turnstile === 'undefined' || turnstileWidgetId === null) {
                return;
            }

            turnstile.reset(turnstileWidgetId);
        }

        function <?php echo $turnstileLoadFunc ?>() {
            if (typeof turnstile === 'undefined') {
                console.error('Turnstile API is unavailable');
                return;
            }

            if (!TURNSTILE_SITEKEY) {
                console.error('Turnstile sitekey is empty');
                return;
            }

            turnstileWidgetId = turnstile.render('#<?php echo $turnstileContainerId ?>', {
                sitekey: TURNSTILE_SITEKEY,
                theme: 'auto',
                size: 'flexible',
                language: TURNSTILE_LANGUAGE,
                callback: function(token) {
                    <?php echo $verifyFuncName ?>(TURNSTILE_MARKER, token);
                },
                'expired-callback': function() {
                    resetTurnstileWidget();
                },
                'timeout-callback': function() {
                    resetTurnstileWidget();
                },
                'error-callback': function(errorCode) {
                    console.error('Turnstile error:', errorCode);
                },
                'unsupported-callback': function() {
                    console.error('Turnstile is unsupported in this browser');
                }
            });
            attachParentIframeHeightSync(document.getElementById('<?php echo $turnstileContainerId ?>'));
        }

        function <?php echo $verifyFuncName ?>(func, turnstileToken) {
            var xhr = new XMLHttpRequest();

            let obj = {
                func: func == undefined ? 'csrf_token' : func,
                csrf_token: CSRF,
                mainFrame: window.top === window.self,
                turnstile_token: turnstileToken == undefined ? '' : turnstileToken,
                turnstileToken: turnstileToken == undefined ? '' : turnstileToken,
                'cf-turnstile-response': turnstileToken == undefined ? '' : turnstileToken,
            };

            let data = null;
            try {
                data = JSON.stringify(obj);
            } catch (e) {
                console.error('Failed to stringify data:', e);
                resetTurnstileWidget();
                return;
            }

            xhr.open('POST', HTTP_ANTIBOT_PATH + 'xhr.php?skin=turnstile&csrf=' + encodeURIComponent(CSRF), true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onload = async function() {
                if (xhr.status < 200 || xhr.status >= 300) {
                    console.error('Unexpected xhr status:', xhr.status);
                    resetTurnstileWidget();
                    return;
                }

                let data = null;
                try {
                    data = JSON.parse(xhr.responseText);
                } catch (e) {
                    console.error('Failed to parse response:', e);
                    resetTurnstileWidget();
                    return;
                }

                CSRF = data.csrf_token;
                if (CSRF == undefined || CSRF == '') {
                    console.log('Error getting csrf_token');
                    resetTurnstileWidget();
                    return;
                }

                if (data.status == 'captcha') {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('csrf', CSRF);
                    window.location.href = currentUrl.toString();
                } else if (data.status == 'allow') {
                    parent.allow();
                } else if (data.status == 'block') {
                    setTimeout(parent.block, 1000);
                } else if (data.status == 'fail') {
                    resetTurnstileWidget();
                } else if (data.status == 'refresh') {
                    parent.refresh();
                } else {
                    console.log(data);
                    resetTurnstileWidget();
                }
            };
            xhr.onerror = function() {
                console.error('Network error occurred');
                resetTurnstileWidget();
            };
            xhr.send(data);
        }
    </script>
</body>
</html>
