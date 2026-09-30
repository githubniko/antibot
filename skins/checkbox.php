<?php
$nonce = \Utility\GenerateRandomName::genKey(17);
$funcName = \Utility\GenerateRandomName::genFuncName();
$tagCheckbox = Utility\GenerateRandomName::genFuncName(4, 6);
$tagBlockInput = Utility\GenerateRandomName::genFuncName(6, 8);

$langMap = [
    "ru" => [
        "title" => "Проверяем ваш Браузер...",
        "checking_human" => "Подтвердите, что вы человек",
        "checking_progress" => "Идет проверка...",
        "stuck_here" => "Stuck here?",
        "send_feedback" => "Send Feedback",
        "failure" => "Сбой",
        "problems" => "Проблемы?",
        "refresh" => "Refresh",
        "session_expired" => "Сессия устарела",
        "time_expired" => "Время истекло",
    ],
    "en" => [
        "title" => "Checking your Browser...",
        "checking_human" => "Confirm you're human",
        "checking_progress" => "Verification in progress...",
        "stuck_here" => "Stuck here?",
        "send_feedback" => "Send Feedback",
        "failure" => "Failure",
        "problems" => "Having problems?",
        "refresh" => "Refresh",
        "session_expired" => "Session expired",
        "time_expired" => "Time expired",
    ],
    "zh" => [
        "title" => "正在检查您的浏览器...",
        "checking_human" => "请确认您是人类",
        "checking_progress" => "正在验证...",
        "stuck_here" => "卡在这里了？",
        "send_feedback" => "发送反馈",
        "failure" => "验证失败",
        "problems" => "遇到问题？",
        "refresh" => "刷新",
        "session_expired" => "会话已过期",
        "time_expired" => "时间已到",
    ],
]
?><html lang="<?php echo $antiBot->Profile->LangAttr ?>" dir="ltr">

<head>
    <meta http-equiv="x-ua-compatible" content="IE=Edge,chrome=1">
    <meta http-equiv="content-security-policy"
        content="default-src 'none'; script-src 'nonce-<?php echo $nonce; ?>' 'unsafe-eval'; script-src-attr 'none'; worker-src blob:; style-src 'unsafe-inline'; img-src 'self'; connect-src 'self'; frame-src 'self' blob:; child-src 'self' blob:; form-action 'none'; base-uri 'self'">
    <title><?php echo $langMap[$antiBot->Profile->Language]['title'] ?></title>
    <meta name="robots" content="noindex,nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <style>
        @keyframes spin {
            to {
                transform: rotate(1turn)
            }
        }

        @keyframes scale-up-center {
            0% {
                transform: scale(.01)
            }

            to {
                transform: scale(1)
            }
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif;
            display: flex;
            min-height: 100vh;
            margin: 0;
        }

        #content {
            align-items: center;
            background-color: #fafafa;
            border: 1px solid #e0e0e0;
            box-sizing: border-box;
            display: flex;
            gap: 7px;
            height: 65px;
            justify-content: space-between;
            user-select: none;
            width: 300px;
            max-width: 300px;
            padding: 0 16px;
        }

        .aw-container {
            align-items: center;
            display: grid;
            gap: 12px;
            grid-template-columns: 30px auto;
        }

        #spinner-i {
            animation: spin 5s linear infinite;
            display: flex;
            height: 30px;
            width: 30px
        }

        .circle {
            stroke-width: 3px;
            stroke-linecap: round;
            stroke: #038127;
            stroke-dasharray: 0, 100, 0;
            stroke-dashoffset: 200;
            stroke-miterlimit: 1;
            stroke-linejoin: round
        }

        #fail-i {
            animation: scale-up-center .6s cubic-bezier(.55, .085, .68, .53) both;
            box-shadow: inset 0 0 0 #de1303;
            border-radius: 50%;
            display: flex;
            height: 30px;
            width: 30px;
            stroke-width: 1px;
            fill: #f8f8f8;
            stroke: #f8f8f8;
            stroke-miterlimit: 10
        }

        .expired-circle,
        .timeout-circle {
            stroke-dasharray: 166;
            stroke-dashoffset: 166;
            stroke-width: 2;
            stroke-miterlimit: 10;
            stroke: #797979;
            fill: #797979
        }

        #expired-i,
        #timeout-i {
            border-radius: 50%;
            box-shadow: inset 0 0 0 #797979;
            display: flex;
            height: 30px;
            width: 30px;
            stroke-width: 1px;
            fill: #f8f8f8;
            stroke: #f8f8f8;
            stroke-miterlimit: 10
        }

        .aw-c {
            align-items: center;
            cursor: pointer;
            display: flex;
            text-align: left
        }

        .aw-lb {
            display: grid;
            place-items: center;
            grid-template-columns: 24px auto;
            gap: 8px;
        }

        .aw-lb input {
            cursor: pointer;
            grid-area: 1/1;
            height: 24px;
            margin: 0;
            opacity: 0;
            width: 24px;
            z-index: 9999
        }

        .aw-lb input:active~.aw-i,
        .aw-lb input:focus~.aw-i {
            border: 2px solid #c44d0e
        }

        .aw-lb input:checked~.aw-i {
            background-color: #fff;
            border-radius: 5px;
            opacity: 1;
            transform: rotate(0deg) scale(1)
        }

        .aw-lb input:checked~.aw-i:after {
            border: solid #c44d0e;
            border-radius: 0;
            border-width: 0 4px 4px 0;
            height: 12px;
            left: 5px;
            top: 0;
            transform: rotate(45deg) scale(1);
            width: 6px
        }

        .aw-lb .aw-i {
            animation: scale-up-center .4s cubic-bezier(.55, .085, .68, .53) both;
            background: #fff;
            border: 2px solid #6d6d6d;
            border-radius: 3px;
            box-sizing: border-box;
            grid-area: 1/1;
            height: 24px;
            transition: all .1s ease-in;
            width: 24px;
            z-index: 9998;
            position: relative;
        }

        .aw-lb .aw-i:after {
            border-radius: 5px;
            content: "";
            position: absolute;
            opacity: 0;
        }

        .aw-lb input:checked~.aw-i:after {
            opacity: 1;
        }

        .error-message {
            color: #de1303;
            font-size: 9px;
            font-weight: 500
        }

        .error-message a:link,
        .error-message a:visited {
            color: #de1303
        }

        .error-message a:active,
        .error-message a:focus,
        .error-message a:hover {
            color: #166379
        }

        #verifying-msg span,
        #failure-msg span,
        #expiry-msg p,
        #timeout-msg p {
            font-size: 14px;
            font-weight: 400;
        }

        /* Темная тема для prefers-color-scheme: dark */
        @media (prefers-color-scheme: dark) {
            body {
                color: #d9d9d9
            }

            #content {
                background-color: #232323;
                border-color: #797979;
            }

            .aw-lb .aw-i {
                background-color: #222;
                border: 2px solid #dadada
            }

            .aw-lb input:active~.aw-i,
            .aw-lb input:focus~.aw-i {
                border: 2px solid #fbad41
            }

            .aw-lb input:checked~.aw-i {
                background-color: #6d6d6d
            }

            .aw-lb input:checked~.aw-i:after {
                border-color: #fbad41
            }

            .expired-circle,
            .timeout-circle {
                stroke: #999;
                fill: #999
            }

            #expired-i,
            #timeout-i {
                box-shadow: inset 0 0 0 #999;
            }

            .error-message,
            .error-message a,
            .error-message a:link,
            .error-message a:visited {
                color: #ffa299
            }
        }
    </style>
</head>

<body>
    <div style="max-width: 300px; height: 65px;">
        <div id="content">
            <div id="<?php echo $tagBlockInput ?>" style="display: grid;">
                <div class="aw-c" role="alert" style="display: flex;">
                    <label class="aw-lb"><input type="checkbox" id="<?php echo $tagCheckbox ?>"><span class="aw-i"></span><span
                            class="aw-lb-t"><?php echo $langMap[$antiBot->Profile->Language]['checking_human'] ?></span></label>
                </div>
            </div>
            <div id="verifying" class="aw-container" style="display: none;">
                <div class="spinner-container">
                    <svg id="spinner-i" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg"
                        aria-hidden="true" class="unspun">
                        <line x1="15" x2="15" y1="1.5" y2="5.5" class="circle"></line>
                        <line x1="24.5459" x2="24.5459" y1="5.45405" y2="10.45405"
                            transform="rotate(45 24.5459 5.45405)" class="circle"></line>
                        <line x1="28.5" x2="28.5" y1="15" y2="20" transform="rotate(90 28.5 15)" class="circle"></line>
                        <line x1="24.5459" x2="24.5459" y1="24.546" y2="29.546" transform="rotate(135 24.5459 24.546)"
                            class="circle"></line>
                        <line x1="15" x2="15" y1="28.5" y2="33.5" transform="rotate(180 15 28.5)" class="circle"></line>
                        <line x1="5.4541" x2="5.4541" y1="24.5459" y2="29.5459" transform="rotate(-135 5.4541 24.5459)"
                            class="circle"></line>
                        <line x1="1.5" x2="1.5" y1="15" y2="20" transform="rotate(-90 1.5 15)" class="circle"></line>
                        <line x1="5.45408" x2="5.45408" y1="5.45404" y2="10.45404"
                            transform="rotate(-45 5.45408 5.45404)" class="circle"></line>
                    </svg>
                </div>
                <div id="verifying-msg"><span id="verifying-text"><?php echo $langMap[$antiBot->Profile->Language]['checking_progress'] ?></span><br>
                    <div id="error-overrun" class="error-message" style="display: none;"><span id="fr-overrun"><?php echo $langMap[$antiBot->Profile->Language]['stuck_here'] ?></span>
                        <a href="#refresh" id="fr-overrun-link"><?php echo $langMap[$antiBot->Profile->Language]['send_feedback'] ?></a>
                    </div>
                </div>
            </div>
            <div id="fail" class="aw-container" role="alert" style="display: none;">
                <svg id="fail-i" viewBox="0 0 30 30" aria-hidden="true" fill="none">
                    <circle class="failure-circle" cx="15" cy="15" r="15" fill="none"></circle>
                    <path class="failure-cross"
                        d="M15.9288 16.2308H13.4273L13.073 7H16.2832L15.9288 16.2308ZM14.6781 19.1636C15.1853 19.1636 15.5918 19.3129 15.8976 19.6117C16.2103 19.9105 16.3666 20.2927 16.3666 20.7583C16.3666 21.2169 16.2103 21.5956 15.8976 21.8944C15.5918 22.1932 15.1853 22.3425 14.6781 22.3425C14.1778 22.3425 13.7713 22.1932 13.4586 21.8944C13.1529 21.5956 13 21.2169 13 20.7583C13 20.2997 13.1529 19.921 13.4586 19.6222C13.7713 19.3164 14.1778 19.1636 14.6781 19.1636Z">
                    </path>
                </svg>
                <div id="failure-msg"><span id="fail-text"><?php echo $langMap[$antiBot->Profile->Language]['failure'] ?></span>
                    <div id="having-trouble-message" class="error-message"><span id="fr-helper"><?php echo $langMap[$antiBot->Profile->Language]['problems'] ?></span><a
                            href="#" id="fr-helper-link"><?php echo $langMap[$antiBot->Profile->Language]['refresh'] ?></a></div>
                </div>
            </div>
            <div id="expired" class="aw-container" role="alert" style="display: none;">
                <svg id="expired-i" viewBox="0 0 30 30" aria-hidden="true">
                    <circle class="expired-circle" cx="15" cy="15" r="15"></circle>
                    <path class="expired-p1"
                        d="M15.3125 6H13V16.7184L19.2438 23.2108L20.9088 21.6094L15.3125 15.7877V6Z">
                    </path>
                </svg>
                <div id="expiry-msg">
                    <p id="expired-text"><?php echo $langMap[$antiBot->Profile->Language]['session_expired'] ?><span id="full-stop-expired-text">. </span><a href="#refresh"
                            id="expired-refresh-link"><?php echo $langMap[$antiBot->Profile->Language]['refresh'] ?></a></p>
                </div>
            </div>
            <div id="timeout" class="aw-container" role="alert" style="display: none;">
                <svg id="timeout-i" viewBox="0 0 30 30" aria-hidden="true">
                    <circle class="timeout-circle" cx="15" cy="15" r="15"></circle>
                    <path class="timeout-p1"
                        d="M15.3125 6H13V16.7184L19.2438 23.2108L20.9088 21.6094L15.3125 15.7877V6Z">
                    </path>
                </svg>
                <div id="timeout-msg">
                    <p id="timeout-text"><?php echo $langMap[$antiBot->Profile->Language]['time_expired'] ?><span id="full-stop-timeout-text">. </span><a href="#refresh"
                            id="timeout-refresh-link"><?php echo $langMap[$antiBot->Profile->Language]['refresh'] ?></a></p>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript" nonce="<?php echo $nonce ?>">
        let CSRF = "<?php echo $_REQUEST["csrf"] ?>";
        var HTTP_ANTIBOT_PATH = '<?php echo $antiBot->Config->ANTIBOT_PATH; ?>';

        const input = document.getElementById("<?php echo $tagCheckbox ?>");
        const blockInput = document.getElementById("<?php echo $tagBlockInput ?>");
        const blockVerifying = document.getElementById("verifying");
        const blockFail = document.getElementById("fail");

        // Убирает все элементы
        function displayNone() {
            blockInput.style.display = "none";
            blockVerifying.style.display = "none";
            input.checked = '';
        }

        function fail() {
            blockFail.style.display = "";
        }

        function <?php echo $funcName ?>(func) {
            var xhr = new XMLHttpRequest();
            var visitortime = new Date();

            let obj = {
                func: func == undefined ? 'csrf_token' : func,
                csrf_token: CSRF,
                mainFrame: window.top === window.self,
            };

            let data = null;
            try {
                data = JSON.stringify(obj);
            } catch (e) {
                console.error('Failed to stringify data:', e);
            }

            xhr.open('POST', HTTP_ANTIBOT_PATH + 'xhr.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            xhr.onload = async function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    var data = JSON.parse(xhr.responseText);

                    CSRF = data.csrf_token;
                    if (CSRF == undefined || CSRF == '') {
                        console.log('Error getting csrf_token');
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
                        displayNone();
                        fail();
                    } else if (data.status == 'refresh') {
                        parent.refresh();
                    } else {
                        console.log(data);
                    }
                }
            };

            xhr.onerror = function() {
                console.error('Network error occurred');
            };

            xhr.send(data);
        }

        input.addEventListener('click', function(event) {
            if (this.checked) {
                blockInput.style.display = "none";
                blockVerifying.style.display = "";
                <?php echo $funcName ?>('<?php echo $antiBot->Marker->getNameMarker() ?>');
            }
        });

        document.addEventListener('click', function(e) {
            if (e.target.matches('#timeout-refresh-link, #expired-refresh-link, #fr-helper-link')) {
                e.preventDefault();
                parent.location.reload();
            }
        });
    </script>
</body>

</html>