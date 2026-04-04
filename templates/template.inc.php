<?
$tagID = Utility\GenerateRandomName::genFuncName(4, 6);
?><html lang="<?= $this->Profile->LangAttr ?>" dir="ltr">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Один момент…</title>
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta name="robots" content="noindex,nofollow">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta http-equiv="refresh" content="390">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
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

        html {
            line-height: 1.15;
            -webkit-text-size-adjust: 100%;
            color: #313131;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica Neue, Arial, Noto Sans, sans-serif, Apple Color Emoji, Segoe UI Emoji, Segoe UI Symbol, Noto Color Emoji
        }

        body {
            display: flex;
            flex-direction: column;
            height: 100vh;
            min-height: 100vh
        }

        .main-content {
            margin: 8rem auto;
            max-width: 60rem;
            padding-left: 1.5rem
        }

        .h2 {
            font-size: 1.5rem;
            font-weight: 500;
            line-height: 2.25rem
        }

        body.no-js .loading-spinner {
            visibility: hidden
        }

        body.theme-dark {
            background-color: #222;
            color: #d9d9d9
        }

        body.theme-dark a {
            color: #fff
        }

        body.theme-dark a:hover {
            color: #ee730a;
            text-decoration: underline
        }

        body.theme-dark .lds-ring div {
            border-color: #999 transparent transparent
        }

        body.theme-dark .font-red {
            color: #b20f03
        }

        body.theme-dark .ctp-button {
            background-color: #4693ff;
            color: #1d1d1d
        }

        body.theme-light {
            background-color: #fff;
            color: #313131
        }

        body.theme-light a {
            color: #0051c3
        }

        body.theme-light a:hover {
            color: #ee730a;
            text-decoration: underline
        }

        body.theme-light .lds-ring div {
            border-color: #595959 transparent transparent
        }

        body.theme-light .font-red {
            color: #fc574a
        }

        body.theme-light .ctp-button {
            background-color: #003681;
            border-color: #003681;
            color: #fff
        }

        a {
            background-color: transparent;
            color: #0051c3;
            text-decoration: none;
            transition: color .15s ease
        }

        a:hover {
            color: #ee730a;
            text-decoration: underline
        }

        .main-content {
            margin: 8rem auto;
            max-width: 60rem;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
            width: 100%
        }

        .main-content .loading-spinner {
            height: 76.391px
        }

        .spacer {
            margin: 2rem 0
        }

        .spacer-top {
            margin-top: 4rem
        }

        .spacer-bottom {
            margin-bottom: 2rem
        }

        .heading-favicon {
            height: 2rem;
            margin-right: .5rem;
            width: 2rem
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

        .error-message.ltr {
            direction: ltr
        }

        .error-message.ltr #fr-overrun {
            margin-left: 0;
            margin-right: .25em
        }

        .main-wrapper {
            align-items: center;
            display: flex;
            flex: 1;
            flex-direction: column
        }

        .font-red {
            color: #b20f03
        }

        .h1 {
            font-size: 2.5rem;
            font-weight: 500;
            line-height: 3.75rem
        }

        .h2 {
            font-weight: 500
        }

        .core-msg,
        .h2 {
            font-size: 1.5rem;
            line-height: 2.25rem
        }

        .body-text,
        .core-msg {
            font-weight: 400
        }

        .body-text {
            font-size: 1rem;
            line-height: 1.25rem
        }

        .text-center {
            text-align: center
        }

        .ctp-button {
            background-color: #0051c3;
            border: .063rem solid #0051c3;
            border-radius: .313rem;
            color: #fff;
            cursor: pointer;
            font-size: .875rem;
            line-height: 1.313rem;
            margin: 2rem 0;
            padding: .375rem 1rem;
            transition-duration: .2s;
            transition-property: background-color, border-color, color;
            transition-timing-function: ease
        }

        .ctp-button:hover {
            background-color: #003681;
            border-color: #003681;
            color: #fff;
            cursor: pointer
        }

        .footer {
            font-size: .75rem;
            line-height: 1.125rem;
            margin: 0 auto;
            max-width: 60rem;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
            width: 100%
        }

        .footer-inner {
            border-top: 1px solid #d9d9d9;
            padding-bottom: 1rem;
            padding-top: 1rem
        }

        .clearfix:after {
            clear: both;
            content: "";
            display: table
        }

        .clearfix .column {
            float: left;
            padding-right: 1.5rem;
            width: 50%
        }

        .diagnostic-wrapper {
            margin-bottom: .5rem
        }

        .footer .ray-id {
            text-align: center
        }

        .footer .ray-id code {
            font-family: monaco, courier, monospace
        }

        .core-msg,
        .zone-name-title {
            overflow-wrap: break-word
        }

        @media (width <=720px) {
            .main-content {
                margin-top: 4rem
            }

            .h2 {
                font-size: 1.25rem;
                line-height: 1.5rem
            }

            .main-content {
                margin-top: 4rem
            }

            .heading-favicon {
                height: 1.5rem;
                width: 1.5rem
            }

            .h1 {
                font-size: 1.5rem;
                line-height: 1.75rem
            }

            .h2 {
                font-size: 1.25rem
            }

            .core-msg,
            .h2 {
                line-height: 1.5rem
            }

            .core-msg {
                font-size: 1rem
            }

            .diagnostic-wrapper {
                display: flex;
                flex-wrap: wrap;
                justify-content: center
            }

            .clearfix:after {
                clear: none;
                content: none;
                display: initial;
                text-align: center
            }

            .column {
                padding-bottom: 2rem
            }

            .clearfix .column {
                float: none;
                padding: 0;
                width: auto;
                word-break: keep-all
            }

            .zone-name-title {
                margin-bottom: 1rem
            }
        }

        .loading-spinner {
            height: 76.391px
        }

        .lds-ring {
            display: inline-block;
            position: relative
        }

        .lds-ring,
        .lds-ring div {
            height: 1.875rem;
            width: 1.875rem
        }

        .lds-ring div {
            animation: lds-ring 1.2s cubic-bezier(.5, 0, .5, 1) infinite;
            border: .3rem solid transparent;
            border-radius: 50%;
            border-top-color: #313131;
            box-sizing: border-box;
            display: block;
            position: absolute
        }

        .lds-ring div:first-child {
            animation-delay: -.45s
        }

        .lds-ring div:nth-child(2) {
            animation-delay: -.3s
        }

        .lds-ring div:nth-child(3) {
            animation-delay: -.15s
        }

        @keyframes lds-ring {
            0% {
                transform: rotate(0deg)
            }

            to {
                transform: rotate(1turn)
            }
        }

        @media (prefers-color-scheme:dark) {
            body {
                background-color: #222;
                color: #d9d9d9
            }

            body a {
                color: #fff
            }

            body a:hover {
                color: #ee730a;
                text-decoration: underline
            }

            body .lds-ring div {
                border-color: #999 transparent transparent
            }

            body .font-red {
                color: #b20f03
            }

            body .ctp-button {
                background-color: #4693ff;
                color: #1d1d1d
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

<body class="no-js">
    <div class="main-wrapper" role="main">
        <div class="main-content">
            <h1 class="zone-name-title h1"><?= $this->Config->SERVER_NAME_NORMALIZE ?></h1>
            <p id="pSht7" class="h2 spacer-bottom" data-translate="checking_human">Проверяем, человек ли вы. Это может занять несколько секунд.</p>
            <div id="uHkM6" style="display: none;">
            </div>
            <div id="InsTY1" class="spacer loading-spinner" style="display: block; visibility: visible;">
                <div class="lds-ring">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>
            <div id="LfAMd3" class="core-msg spacer spacer-top" data-translate="security_check">Сначала <?= $this->Config->SERVER_NAME_NORMALIZE ?> необходимо проверить безопасность
                вашего подключения.</div>
            <div id="tWuBw3" style="display: none;">
                <div id="challenge-success-text" class="h2" data-translate="success_text">Проверка выполнена успешно</div>
                <div class="core-msg spacer" data-translate="waiting_response">Ожидание ответа <?= $this->Config->SERVER_NAME_NORMALIZE ?>...</div>
            </div><noscript>
                <div class="h2"><span id="challenge-error-text" data-translate="enable_js">Enable JavaScript and cookies to continue</span></div>
            </noscript>
        </div>
    </div>
    <script type="text/javascript">
        var HTTP_ANTIBOT_PATH = '<?= $this->Config->ANTIBOT_PATH; ?>';
        var METRIKA_ID = '<?= $this->metrika; ?>';
        var REMOTE_ADDR = '<?= $this->Profile->IP; ?>';
        var UTM_REFERRER = '<?= $this->utm_referrer; ?>';
        var SAVE_REFERER = '<?= $this->save_referer; ?>';
        var CSRF = '<?= WAFSystem\CSRF::getInstance($wafsystem)->createCSRF() ?>';

        const head2 = document.getElementById("pSht7");
        const form = document.getElementById("uHkM6");
        const lspinner = document.getElementById("InsTY1");
        const blockSuccessful = document.getElementById("tWuBw3");
        const blockHTTPSecurity = document.getElementById("LfAMd3");

        function displayCaptcha() {
            var iframe = document.createElement("iframe");
            iframe.onload = () => {
                // Показываем содержимое iframe
                spinnerNone();
                form.style.cssText = "";
                head2.textContent = lang['confirm_human'];
            }
            iframe.allow = "cross-origin-isolated; fullscreen; autoplay";
            iframe.sandbox = "allow-same-origin allow-scripts allow-popups";
            iframe.id = "<?= $tagID ?>";
            iframe.tabindex = 0;
            iframe.title = lang['widget_action'];
            iframe.style = "border: none; overflow: hidden; width: 100%; max-width: 100vw; min-width: 300px; min-height:200px;";
            iframe.scrolling = "no";
            iframe.src = HTTP_ANTIBOT_PATH + "xhr.php?skin=<?= $this->getSkinCaptcha() ?>&csrf=" + CSRF;

            // Загружаем, но не показываем iframe
            form.style.cssText = "position: absolute; width: 0; height: 0; overflow: hidden; opacity: 0;";
            form.style.display = "grid";
            form.appendChild(iframe);
        }

        function loadModules() {
            <?
            $antibot = \WAFSystem\WAFSystem::getInstance();
            echo $antibot->FingerPrint->enabled ? "loadScript('js/fp.min.js?" . filemtime($this->Config->DOCUMENT_ROOT . $this->Config->ANTIBOT_PATH . "js/fp.min.js") . "', initFingerPrint);" : '';
            echo $antibot->FPSChecker->enabled ? "loadScript('js/frame_rate.js?" . filemtime($this->Config->DOCUMENT_ROOT . $this->Config->ANTIBOT_PATH . "js/frame_rate.js") . "', callbackFrameRate);" : '';
            ?>

        }

        (function() {
            // Показываем favicon если есть
            setTimeout(() => {
                fetch('/favicon.ico')
                    .then(response => {
                        if (response.ok) {
                            const h1 = document.querySelector('h1');

                            const img = document.createElement('img');
                            img.src = '/favicon.ico';
                            img.className = 'heading-favicon';
                            img.alt = 'Icon <?= $this->Config->SERVER_NAME_NORMALIZE ?>';
                            h1.insertBefore(img, h1.firstChild);
                        }
                    })
                    .catch(() => {}); // Игнорируем ошибки
            }, 0);

            var cpoLang = document.createElement('script');
            cpoLang.src = '<?= $this->Config->ANTIBOT_PATH . 'js/lang.js?' . filemtime($this->Config->DOCUMENT_ROOT . $this->Config->ANTIBOT_PATH . 'js/lang.js'); ?>';
            document.getElementsByTagName('head')[0].appendChild(cpoLang);

            var cpo = document.createElement('script');
            cpo.src = '<?= $this->Config->ANTIBOT_PATH . 'js/api.js?' . filemtime($this->Config->DOCUMENT_ROOT . $this->Config->ANTIBOT_PATH . 'js/api.js'); ?>';
            document.getElementsByTagName('head')[0].appendChild(cpo);
        }());
    </script>
    <div class="footer" role="contentinfo">
        <div class="footer-inner">
            <div class="clearfix diagnostic-wrapper">
                <div class="ray-id">Ray ID: <code><?= $this->Profile->RayID; ?></code></div>
            </div>
            <div class="text-center" id="footer-text"><span data-translate="performance_security">Производительность и безопасность на платформе</span> <a
                    rel="noopener noreferer" href="https://github.com/githubniko/antibot"
                    target="_blank">AntibotWAF</a></div>
        </div>
    </div>
</body>

</html>