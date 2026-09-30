<?php
$nonce = \Utility\GenerateRandomName::genKey(17);
$funcName = \Utility\GenerateRandomName::genFuncName();
$funcNameSucc = Utility\GenerateRandomName::genFuncName(4, 6);

$langMap = [
    "ru" => [
        "title" => "Проверяем ваш Браузер...",
        "action_name" => "→ Передвиньте ползунок →",
        "checking_progress" => "Идет проверка...",
        "stuck_here" => "Stuck here?",
        "send_feedback" => "Send Feedback",
    ],
    "en" => [
        "title" => "Checking your Browser...",
        "action_name" => "→ Slide the slider →",
        "checking_progress" => "Verification in progress...",
        "stuck_here" => "Stuck here?",
        "send_feedback" => "Send Feedback",
    ],
    "zh" => [
        "title" => "正在检查您的浏览器...",
        "action_name" => "→ 滑动滑块 →",
        "checking_progress" => "正在验证...",
        "stuck_here" => "卡在这里了？",
        "send_feedback" => "发送反馈",
    ],
];
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

        @keyframes stroke {
            to {
                stroke-dashoffset: 0
            }
        }

        @keyframes scale {

            0%,
            to {
                transform: none
            }

            50% {
                transform: scaleX(1)
            }
        }

        @keyframes fill {
            to {
                transform: scale(1)
            }
        }

        @keyframes fillfail {
            to {
                box-shadow: inset 0 30px 0 0 #de1303
            }
        }

        @keyframes fillfail-offlabel {
            to {
                box-shadow: inset 0 0 0 30px #232323
            }
        }

        @keyframes fillfail-offlabel-dark {
            to {
                box-shadow: inset 0 0 0 30px #fff
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

        @keyframes fade-in {
            0% {
                opacity: 0
            }

            to {
                opacity: 1
            }
        }

        @keyframes fireworks {
            0% {
                opacity: 0;
                transform: scale(0)
            }

            50% {
                opacity: 1;
                transform: scale(1.5)
            }

            to {
                opacity: 0;
                transform: scale(2)
            }
        }

        @keyframes firework {
            0% {
                opacity: 0;
                stroke-dashoffset: 8
            }

            30% {
                opacity: 1
            }

            to {
                stroke-dashoffset: -8
            }
        }

        @keyframes unspin {
            40% {
                stroke-width: 1px;
                stroke-linecap: square;
                stroke-dashoffset: 192
            }

            to {
                stroke-width: 0
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

        body.theme-dark #challenge-success-text {
            background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgZmlsbD0ibm9uZSIgdmlld0JveD0iMCAwIDI2IDI2Ij48cGF0aCBmaWxsPSIjZDlkOWQ5IiBkPSJNMTMgMGExMyAxMyAwIDEgMCAwIDI2IDEzIDEzIDAgMCAwIDAtMjZtMCAyNGExMSAxMSAwIDEgMSAwLTIyIDExIDExIDAgMCAxIDAgMjIiLz48cGF0aCBmaWxsPSIjZDlkOWQ5IiBkPSJtMTAuOTU1IDE2LjA1NS0zLjk1LTQuMTI1LTEuNDQ1IDEuMzg1IDUuMzcgNS42MSA5LjQ5NS05LjYtMS40Mi0xLjQwNXoiLz48L3N2Zz4=")
        }

        body.theme-dark #challenge-error-text {
            background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgZmlsbD0ibm9uZSI+PHBhdGggZmlsbD0iI0IyMEYwMyIgZD0iTTE2IDNhMTMgMTMgMCAxIDAgMTMgMTNBMTMuMDE1IDEzLjAxNSAwIDAgMCAxNiAzbTAgMjRhMTEgMTEgMCAxIDEgMTEtMTEgMTEuMDEgMTEuMDEgMCAwIDEtMTEgMTEiLz48cGF0aCBmaWxsPSIjQjIwRjAzIiBkPSJNMTcuMDM4IDE4LjYxNUgxNC44N0wxNC41NjMgOS41aDIuNzgzem0tMS4wODQgMS40MjdxLjY2IDAgMS4wNTcuMzg4LjQwNy4zODkuNDA3Ljk5NCAwIC41OTYtLjQwNy45ODQtLjM5Ny4zOS0xLjA1Ny4zODktLjY1IDAtMS4wNTYtLjM4OS0uMzk4LS4zODktLjM5OC0uOTg0IDAtLjU5Ny4zOTgtLjk4NS40MDYtLjM5NyAxLjA1Ni0uMzk3Ii8+PC9zdmc+")
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

        body.theme-light #challenge-success-text {
            background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgZmlsbD0ibm9uZSIgdmlld0JveD0iMCAwIDI2IDI2Ij48cGF0aCBmaWxsPSIjMzEzMTMxIiBkPSJNMTMgMGExMyAxMyAwIDEgMCAwIDI2IDEzIDEzIDAgMCAwIDAtMjZtMCAyNGExMSAxMSAwIDEgMSAwLTIyIDExIDExIDAgMCAxIDAgMjIiLz48cGF0aCBmaWxsPSIjMzEzMTMxIiBkPSJtMTAuOTU1IDE2LjA1NS0zLjk1LTQuMTI1LTEuNDQ1IDEuMzg1IDUuMzcgNS42MSA5LjQ5NS05LjYtMS40Mi0xLjQwNXoiLz48L3N2Zz4=")
        }

        body.theme-light #challenge-error-text {
            background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgZmlsbD0ibm9uZSI+PHBhdGggZmlsbD0iI2ZjNTc0YSIgZD0iTTE2IDNhMTMgMTMgMCAxIDAgMTMgMTNBMTMuMDE1IDEzLjAxNSAwIDAgMCAxNiAzbTAgMjRhMTEgMTEgMCAxIDEgMTEtMTEgMTEuMDEgMTEuMDEgMCAwIDEtMTEgMTEiLz48cGF0aCBmaWxsPSIjZmM1NzRhIiBkPSJNMTcuMDM4IDE4LjYxNUgxNC44N0wxNC41NjMgOS41aDIuNzgzem0tMS4wODQgMS40MjdxLjY2IDAgMS4wNTcuMzg4LjQwNy4zODkuNDA3Ljk9NCAwIC41OTYtLjQwNy45ODQtLjM5Ny4zOS0xLjA1Ny4zODktLjY1IDAtMS4wNTYtLjM4OS0uMzk4LS4zODktLjM5OC0uOTg0IDAtLjU5Ny4zOTgtLjk4NS40MDYtLjM5NyAxLjA1Ni0uMzk3Ii8+PC9zdmc+")
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

        .aw-container {
            align-items: center;
            display: grid;
            gap: 12px;
            grid-template-columns: 30px auto;
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


        #content {
            align-items: center;
            border: 0px solid #e0e0e0;
            box-sizing: border-box;
            display: flex;
            gap: 7px;
            justify-content: space-between;
            user-select: none;
        }

        #expired-text,
        #overrun-text,
        #timeout-text {
            font-size: 14px;
            font-weight: 400;
            margin: 0;
            text-align: inherit
        }

        #error-overrun {
            margin-top: 2px
        }

        #error-overrun,
        #expired-refresh-link,
        #expired-text,
        #fr-overrun,
        #timeout-refresh-link,
        #timeout-text {
            display: inline-block
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

        .cb-container {
            align-items: center;
            display: grid;
            gap: 12px;
            grid-template-columns: 30px auto;
            margin-left: 16px
        }

        #overrun-i,
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
            box-shadow: inset 0 0 0 #de1303
        }

        #fail-i {
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
            stroke-miterlimit: 10;
            animation: scale .3s ease-in-out .9s both
        }

        .cb-c {
            align-items: center;
            cursor: pointer;
            display: flex;
            margin-left: 16px;
            text-align: left
        }

        .cb-lb {
            display: grid;
            place-items: center
        }

        .cb-lb input {
            cursor: pointer;
            grid-area: 1/1;
            height: 24px;
            margin: 0;
            opacity: 0;
            width: 24px;
            z-index: 9999
        }

        .cb-lb input:active~.cb-i,
        .cb-lb input:focus~.cb-i {
            border: 2px solid #c44d0e
        }

        .cb-lb input:checked~.cb-i {
            background-color: #fff;
            border-radius: 5px;
            opacity: 1;
            transform: rotate(0deg) scale(1)
        }

        .cb-lb input:checked~.cb-i:after {
            border: solid #c44d0e;
            border-radius: 0;
            border-width: 0 4px 4px 0;
            height: 12px;
            left: 5px;
            top: 0;
            transform: rotate(45deg) scale(1);
            width: 6px
        }

        .cb-lb .cb-i {
            animation: scale-up-center .4s cubic-bezier(.55, .085, .68, .53) both;
            background: #fff;
            border: 2px solid #6d6d6d;
            border-radius: 3px;
            box-sizing: border-box;
            grid-area: 1/1;
            height: 24px;
            transition: all .1s ease-in;
            width: 24px;
            z-index: 9998
        }

        .cb-lb .cb-i:after {
            border-radius: 5px;
            content: "";
            position: absolute
        }

        .cb-lb .cb-lb-t {
            grid-column: 2;
            margin-left: 8px
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

        #challenge-error-text {
            background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgZmlsbD0ibm9uZSI+PHBhdGggZmlsbD0iI2ZjNTc0YSIgZD0iTTE2IDNhMTMgMTMgMCAxIDAgMTMgMTNBMTMuMDE1IDEzLjAxNSAwIDAgMCAxNiAzbTAgMjRhMTEgMTEgMCAxIDEgMTEtMTEgMTEuMDEgMTEuMDEgMCAwIDEtMTEgMTEiLz48cGF0aCBmaWxsPSIjZmM1NzRhIiBkPSJNMTcuMDM4IDE4LjYxNUgxNC44N0wxNC41NjMgOS41aDIuNzgzem0tMS4wODQgMS40MjdxLjY2IDAgMS4wNTcuMzg4LjQwNy4zODkuNDA3Ljk5NCAwIC41OTYtLjQwNy45ODQtLjM5Ny4zOS0xLjA1Ny4zODktLjY1IDAtMS4wNTYtLjM4OS0uMzk4LS4zODktLjM5OC0uOTg0IDAtLjU5Ny4zOTgtLjk4NS40MDYtLjM5NyAxLjA1Ni0uMzk3Ii8+PC9zdmc+");
            padding-left: 34px
        }

        #challenge-error-text,
        #challenge-success-text {
            background-repeat: no-repeat;
            background-size: contain
        }

        #challenge-success-text {
            background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgZmlsbD0ibm9uZSIgdmlld0JveD0iMCAwIDI2IDI2Ij48cGF0aCBmaWxsPSIjMzEzMTMxIiBkPSJNMTMgMGExMyAxMyAwIDEgMCAwIDI2IDEzIDEzIDAgMCAwIDAtMjZtMCAyNGExMSAxMSAwIDEgMSAwLTIyIDExIDExIDAgMCAxIDAgMjIiLz48cGF0aCBmaWxsPSIjMzEzMTMxIiBkPSJtMTAuOTU1IDE2LjA1NS0zLjk1LTQuMTI1LTEuNDQ1IDEuMzg1IDUuMzcgNS42MSA5LjQ5NS05LjYtMS40Mi0xLjQwNXoiLz48L3N2Zz4=");
            padding-left: 42px
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

        .rtl .heading-favicon {
            margin-left: .5rem;
            margin-right: 0
        }

        .rtl #challenge-success-text {
            background-position: 100%;
            padding-left: 0;
            padding-right: 42px
        }

        .rtl #challenge-error-text {
            background-position: 100%;
            padding-left: 0;
            padding-right: 34px
        }

        .rtl #expired-i,
        .rtl #fail-i,
        .rtl #overrun-i,
        .rtl #spinner-i,
        .rtl #success-i,
        .rtl #timeout-i {
            left: 255px
        }

        .challenge-content .loading-spinner {
            height: 76.391px
        }

        /* Стили для слайдера */
        .slider-wrapper {
            transform-origin: center;
            width: 100%;
            padding: 10px 0;
        }

        .slider-container {
            position: relative;
            width: 300px;
            margin: 0 auto;
            margin-top: 40px;
            text-align: center;
            transition: opacity 0.5s ease;
            padding: 10px;
        }

        .slider-track {
            width: 100%;
            height: 40px;
            background: #313131;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .slider-thumb {
            width: 40px;
            height: 40px;
            background: #4CAF50;
            border-radius: 50%;
            position: absolute;
            left: 0;
            cursor: pointer;
            z-index: 2;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .slider-progress {
            height: 100%;
            background: linear-gradient(to right, #8BC34A, #4CAF50);
            width: 0;
            position: absolute;
            left: 0;
            border-radius: 20px;
        }

        .slider-text {
            margin-top: 10px;
            user-select: none;
            font-weight: 500;
            color: #fff;
            position: absolute;
            top: 9px;
            left: 52px;
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

            body #challenge-success-text {
                background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgZmlsbD0ibm9uZSIgdmlld0JveD0iMCAwIDI2IDI2Ij48cGF0aCBmaWxsPSIjZDlkOWQ5IiBkPSJNMTMgMGExMyAxMyAwIDEgMCAwIDI2IDEzIDEzIDAgMCAwIDAtMjZtMCAyNGExMSAxMSAwIDEgMSAwLTIyIDExIDExIDAgMCAxIDAgMjIiLz48cGF0aCBmaWxsPSIjZDlkOWQ5IiBkPSJtMTAuOTU1IDE2LjA1NS0zLjk1LTQuMTI1LTEuNDQ1IDEuMzg1IDUuMzcgNS42MSA5LjQ5NS05LjYtMS40Mi0xLjQwNXoiLz48L3N2Zz4=")
            }

            body #challenge-error-text {
                background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgZmlsbD0ibm9uZSI+PHBhdGggZmlsbD0iI0IyMEYwMyIgZD0iTTE2IDNhMTMgMTMgMCAxIDAgMTMgMTNBMTMuMDE1IDEzLjAxNSAwIDAgMCAxNiAzbTAgMjRhMTEgMTEgMCAxIDEgMTEtMTEgMTEuMDEgMTEuMDEgMCAwIDEtMTEgMTEiLz48cGF0aCBmaWxsPSIjQjIwRjAzIiBkPSJNMTcuMDM4IDE4LjYxNUgxNC44N2wxNC41NjMtOS41aDIuNzgzem0tMS4wODQgMS40MjdxLjY2IDAgMS4wNTcuMzg4LjQwNy4zODkuNDA3Ljk5NCAwIC41OTYtLjQwNy45ODQtLjM5Ny4zOS0xLjA1Ny4zODktLjY1IDAtMS4wNTYtLjM4OS0uMzk4LS4zODktLjM5OC0uOTg0IDAtLjU5Ny4zOTgtLjk4NS40MDYtLjM5NyAxLjA1Ni0uMzk3Ii8+PC9zdmc+")
            }

            body {
                background-color: #222;
                color: #d9d9d9
            }

            .cb-lb .cb-i {
                background-color: #222;
                border: 2px solid #dadada
            }

            .cb-lb input:active~.cb-i,
            .cb-lb input:focus~.cb-i {
                border: 2px solid #fbad41
            }

            .cb-lb input:checked~.cb-i {
                background-color: #6d6d6d
            }

            .cb-lb input:checked~.cb-i:after {
                border-color: #fbad41
            }

            .expired-circle,
            .timeout-circle {
                stroke-dasharray: 166;
                stroke-dashoffset: 166;
                stroke-width: 2;
                stroke-miterlimit: 10;
                stroke: #999;
                fill: #999
            }

            #expired-i,
            #timeout-i {
                border-radius: 50%;
                box-shadow: inset 0 0 0 #999;
                display: flex;
                height: 30px;
                width: 30px;
                stroke-width: 1px;
                fill: #f8f8f8;
                stroke: #f8f8f8;
                stroke-miterlimit: 10
            }

            .error-message,
            .error-message a,
            .error-message a:link,
            .error-message a:visited {
                color: #ffa299
            }

            /* Темная тема для слайдера */
            .slider-track {
                background: #444;
            }

            .slider-thumb {
                background: #4693ff;
            }

            .slider-progress {
                background: #1a73e8;
            }
        }
    </style>
</head>

<body>
    <div style="max-width: 300px; height: 130px;">
        <div id="content">
            <!-- Слайдер-капча -->
            <div class="slider-wrapper" id="slider-wrapper">
                <div class="slider-container" id="slider-captcha">
                    <div class="slider-track">
                        <div class="slider-progress"></div>
                        <div class="slider-thumb" id="slider-thumb"></div>
                    </div>
                    <div class="slider-text"><?php echo $langMap[$antiBot->Profile->Language]['action_name'] ?></div>
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
        </div>
    </div>
    <script type="text/javascript" nonce="<?php echo $nonce ?>">
        let CSRF = "<?php echo $_REQUEST["csrf"] ?>";
        var HTTP_ANTIBOT_PATH = '<?php echo $antiBot->Config->ANTIBOT_PATH; ?>';

        // Код для слайдера
        const wrapper = document.getElementById('slider-wrapper');
        const thumb = document.getElementById('slider-thumb');
        const track = document.querySelector('.slider-track');
        const progress = document.querySelector('.slider-progress');
        const captcha = document.getElementById('slider-captcha');
        const loadingSpinner = document.getElementById('verifying');
        let isDragging = false;

        function getRandomAngle(min, max) {
            return Math.random() * (max - min) + min;
        }

        // Рандомный угол наклона (10-30° или -10--30°)
        const angle = getRandomAngle(10, 30) * (Math.random() > 0.5 ? 1 : -1);
        wrapper.style.transform = `rotate(${angle}deg)`;

        // Начальная позиция
        let startX = 0;
        let thumbPosition = 0;
        const thumbWidth = thumb.offsetWidth;
        const maxPosition = track.offsetWidth - thumbWidth;
        progress.style.width = thumbWidth;

        function resetSlider() {
            thumb.style.left = '0';
            progress.style.width = thumbWidth;
            startX = 0;
            thumbPosition = 0;
        }

        // Проверка, находится ли курсор над thumb
        function isCursorOverThumb(clientX, clientY) {
            const thumbRect = thumb.getBoundingClientRect();
            return (
                clientX >= thumbRect.left &&
                clientX <= thumbRect.right &&
                clientY >= thumbRect.top &&
                clientY <= thumbRect.bottom
            );
        }

        // Глобальные обработчики для контроля выхода за пределы thumb
        function handleGlobalMouseMove(e) {
            if (!isDragging) return;

            // Проверяем, находится ли курсор над thumb при движении
            if (!isCursorOverThumb(e.clientX, e.clientY)) {
                // Курсор вышел за пределы thumb - сбрасываем
                isDragging = false;
                resetSlider();
                cleanupEvents();
            }
        }

        function handleGlobalTouchMove(e) {
            if (!isDragging || !e.touches[0]) return;

            const touch = e.touches[0];
            if (!isCursorOverThumb(touch.clientX, touch.clientY)) {
                // Курсор вышел за пределы thumb - сбрасываем
                isDragging = false;
                resetSlider();
                cleanupEvents();
            }
        }

        function handleUp(e) {
            if (!isDragging) return;
            isDragging = false;

            resetSlider();
        }

        // Удаляем глобальные обработчики
        function cleanupEvents() {
            document.removeEventListener('mousemove', handleGlobalMouseMove);
            document.removeEventListener('mouseup', handleUp);
            document.removeEventListener('touchmove', handleGlobalTouchMove);
            document.removeEventListener('touchend', handleUp);
        }

        // Обработчики для мыши
        thumb.addEventListener('mousedown', (e) => {
            isDragging = true;
            const thumbRect = thumb.getBoundingClientRect();
            startX = e.clientX - thumbRect.left;
            e.preventDefault();

            // Добавляем глобальные обработчики для отслеживания курсора
            document.addEventListener('mousemove', handleGlobalMouseMove);
            document.addEventListener('mouseup', handleUp);
        });

        // Для touch-устройств
        thumb.addEventListener('touchstart', (e) => {
            isDragging = true;
            startX = e.touches[0].clientX - thumb.getBoundingClientRect().left;
            if (e.cancelable) {
                e.preventDefault();
            }

            // Добавляем глобальные обработчики для отслеживания тача
            document.addEventListener('touchmove', handleGlobalTouchMove);
            document.addEventListener('touchend', handleUp);
        }, {
            passive: false
        });

        // Основной обработчик перемещения для обновления позиции thumb
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;

            const trackRect = track.getBoundingClientRect();

            let newPosition = e.clientX - trackRect.left - startX;
            newPosition = Math.min(maxPosition, newPosition);

            thumb.style.left = newPosition + 'px';
            thumbPosition = newPosition;
            progress.style.width = newPosition + thumbWidth + 'px';

            const percent = Math.round((thumbPosition / maxPosition) * 100);
            if (percent >= 90) {
                <?php echo $funcNameSucc ?>();
            }
        });

        document.addEventListener('touchmove', (e) => {
            if (!isDragging || !e.touches[0]) return;

            const trackRect = track.getBoundingClientRect();
            const touch = e.touches[0];

            let newPosition = touch.clientX - trackRect.left - startX;
            newPosition = Math.min(maxPosition, newPosition);
            thumb.style.left = newPosition + 'px';
            thumbPosition = newPosition;
            progress.style.width = newPosition + thumbWidth + 'px';

            const percent = Math.round((thumbPosition / maxPosition) * 100);
            if (percent >= 90) {
                <?php echo $funcNameSucc ?>();
            }
        });

        function <?php echo $funcNameSucc ?>() {
            // Плавное исчезновение капчи
            captcha.style.opacity = '0';
            captcha.style.transform = 'translateY(-20px)';
            captcha.style.transition = 'all 0.5s ease';

            // Показываем спиннер загрузки
            wrapper.style.display = 'none';
            loadingSpinner.style.display = 'grid';

            <?php echo $funcName ?>('<?php echo $antiBot->Marker->getNameMarker() ?>');
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
                    // Перезагружает капчу
                    if (data.status == 'captcha') {
                        const currentUrl = new URL(window.location.href);
                        currentUrl.searchParams.set('csrf', CSRF);
                        window.location.href = currentUrl.toString();
                    }
                    // Успешно прошел капчу
                    else if (data.status == 'allow') {
                        parent.allow();
                    }
                    // НЕ прошел капчу, пользователь заблокирован
                    else if (data.status == 'block') {
                        setTimeout(parent.block, 1000);
                    }
                    // Технические ошибки, когда требуется вмешательство тех. специалиста
                    else if (data.status == 'fail') {}
                    // Обновить страницу
                    else if (data.status == 'refresh') {
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
    </script>
</body>

</html>