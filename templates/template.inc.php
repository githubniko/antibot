<html lang="en-US" dir="ltr">

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
            background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgZmlsbD0ibm9uZSI+PHBhdGggZmlsbD0iI2ZjNTc0YSIgZD0iTTE2IDNhMTMgMTMgMCAxIDAgMTMgMTNBMTMuMDE1IDEzLjAxNSAwIDAgMCAxNiAzbTAgMjRhMTEgMTEgMCAxIDEgMTEtMTEgMTEuMDEgMTEuMDEgMCAwIDEtMTEgMTEiLz48cGF0aCBmaWxsPSIjZmM1NzRhIiBkPSJNMTcuMDM4IDE4LjYxNUgxNC44N0wxNC41NjMgOS41aDIuNzgzem0tMS4wODQgMS40MjdxLjY2IDAgMS4wNTcuMzg4LjQwNy4zODkuNDA3Ljk5NCAwIC41OTYtLjQwNy45ODQtLjM5Ny4zOS0xLjA1Ny4zODktLjY1IDAtMS4wNTYtLjM4OS0uMzk4LS4zODktLjM5OC0uOTg0IDAtLjU5Ny4zOTgtLjk4NS40MDYtLjM5NyAxLjA1Ni0uMzk3Ii8+PC9zdmc+")
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
                background-image: url("data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgZmlsbD0ibm9uZSI+PHBhdGggZmlsbD0iI0IyMEYwMyIgZD0iTTE2IDNhMTMgMTMgMCAxIDAgMTMgMTNBMTMuMDE1IDEzLjAxNSAwIDAgMCAxNiAzbTAgMjRhMTEgMTEgMCAxIDEgMTEtMTEgMTEuMDEgMTEuMDEgMCAwIDEtMTEgMTEiLz48cGF0aCBmaWxsPSIjQjIwRjAzIiBkPSJNMTcuMDM4IDE4LjYxNUgxNC44N0wxNC41NjMgOS41aDIuNzgzem0tMS4wODQgMS40MjdxLjY2IDAgMS4wNTcuMzg4LjQwNy4zODkuNDA3Ljk5NCAwIC41OTYtLjQwNy45ODQtLjM5Ny4zOS0xLjA1Ny4zODktLjY1IDAtMS4wNTYtLjM4OS0uMzk4LS4zODktLjM5OC0uOTg0IDAtLjU5Ny4zOTgtLjk4NS40MDYtLjM5NyAxLjA1Ni0uMzk3Ii8+PC9zdmc+")
            }

            body {
                background-color: #222;
                color: #d9d9d9
            }

            #content {
                background-color: #232323;
                border-color: #797979;
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
        }
                /* Стили для слайдера */
.slider-wrapper {
    width: 100%;
    margin: 30px 0 15px;
    padding: 10px 0;
    text-align: center;
}

.slider-container {
    width: 300px;
    margin: 0 auto;
    transition: opacity 0.5s ease;
}

.slider-track {
    width: 100%;
    height: 40px;
    background: #eee;
    border-radius: 20px;
    position: relative;
    overflow: hidden;
    margin: 15px 0;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
}

.slider-thumb {
    width: 40px;
    height: 40px;
    background: #4CAF50;
    border-radius: 50%;
    position: absolute;
    left: 0;
    top: 0;
    cursor: pointer;
    z-index: 2;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    border: 2px solid #fff;
}

.slider-progress {
    height: 100%;
    background: #8BC34A;
    width: 0;
    position: absolute;
    left: 0;
    top: 0;
    transition: width 0.1s ease;
    border-radius: 20px;
}

.slider-text {
    margin-top: 10px;
    user-select: none;
    color: #666;
    font-size: 14px;
}

/* Темная тема для слайдера */
body.theme-dark .slider-track {
    background: #444;
}

body.theme-dark .slider-thumb {
    background: #4693ff;
    border-color: #333;
}

body.theme-dark .slider-progress {
    background: #1a73e8;
}

body.theme-dark .slider-text {
    color: #d9d9d9;
}
    </style>
    <? if(!empty($AB_METRIKA)): ?>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript" >
    (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
    m[i].l=1*new Date();
    for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
    k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
    (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

    ym(<?=$AB_METRIKA; ?>, "init", {
            clickmap:true,
            trackLinks:true,
            accurateTrackBounce:true,
            webvisor:true,
            params:{ip: "<? echo $_SERVER['REMOTE_ADDR']; ?>"}
    });
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/101475381" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
    <? endif; ?>
</head>

<body class="no-js">
    <div class="main-wrapper" role="main">
        <div class="main-content">
            <h1 class="zone-name-title h1"><?= $_SERVER['SERVER_NAME'] ?></h1>
            <p id="pSht7" class="h2 spacer-bottom">Проверяем, человек ли вы. Это может занять несколько секунд.</p>
            <!-- Контейнер для капчи (будет заполнен динамически) -->
            <div id="captcha-container"></div>
            
            <div id="InsTY1" class="spacer loading-spinner" style="display: block; visibility: visible;">
                <div class="lds-ring">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>
            <div id="LfAMd3" class="core-msg spacer spacer-top">Сначала <?= $_SERVER['SERVER_NAME'] ?> необходимо проверить безопасность
                вашего подключения.</div>
            <div id="tWuBw3" style="display: none;">
                <div id="challenge-success-text" class="h2">Проверка выполнена успешно</div>
                <div class="core-msg spacer">Ожидание ответа <?= $_SERVER['SERVER_NAME'] ?>...</div>
            </div>
            <noscript>
                <div class="h2"><span id="challenge-error-text">Enable JavaScript and cookies to continue</span></div>
            </noscript>
        </div>
    </div>
    <script>
        var HTTP_ANTIBOT_PATH = '<?= $this->Config->ANTIBOT_PATH; ?>';
        var METRIKA_ID = '<?= $this->Config->get('main', 'metrika', ''); ?>';
        var REMOTE_ADDR = '<?= $this->Profile->IP; ?>';
        var UTM_REFERRER = '<?= $this->utm_referrer; ?>';
        var SAVE_REFERER = '<?= $this->save_referer; ?>';
        var CAPTCHA_TYPE = '<?= $this->Config->get('main', 'captcha_type', 'checkbox'); ?>';

        (function() {
            // Показываем favicon если есть
            fetch('/favicon.ico')
                .then(response => {
                    if (response.ok) {
                        const h1 = document.querySelector('h1');
                        const img = document.createElement('img');
                        img.src = '/favicon.ico';
                        img.className = 'heading-favicon';
                        img.alt = 'Значок <?= $_SERVER['SERVER_NAME'] ?>';
                        h1.insertBefore(img, h1.firstChild);
                    }
                })
                .catch(() => {});

            // Инициализация капчи
            initCaptcha();
            
            var cpo = document.createElement('script');
            cpo.src = HTTP_ANTIBOT_PATH + 'js/api.js?' + Date.now();
            document.getElementsByTagName('head')[0].appendChild(cpo);
        }());

        function initCaptcha() {
            const container = document.getElementById('captcha-container');
            
            if (CAPTCHA_TYPE === 'slider') {
                // Создаем слайдер капчу
                container.innerHTML = `
                    <div id="uHkM6" style="max-width: 300px; height: auto;">
                        <div class="slider-wrapper" id="slider-wrapper">
                            <div class="slider-container" id="slider-captcha">
                                <h3>Потяните слайдер вправо, чтобы продолжить</h3>
                                <div class="slider-track">
                                    <div class="slider-progress"></div>
                                    <div class="slider-thumb" id="slider-thumb"></div>
                                </div>
                                <p class="slider-text">→ Передвиньте ползунок →</p>
                            </div>
                        </div>
                    </div>
                `;
                initSliderCaptcha();
            } else {
                // Создаем чекбокс капчу (по умолчанию)
                container.innerHTML = `
                    <div id="uHkM6" style="display: none;">
                        <div style="max-width: 300px; height: 65px;">
                            <div id="content">
                                <div id="ihOWn1" style="display: grid;">
                                    <div class="cb-c" role="alert" style="display: flex;">
                                        <label class="cb-lb"><input type="checkbox" id="uiEr3"><span class="cb-i"></span><span class="cb-lb-t">Подтвердите, что вы человек</span></label>
                                    </div>
                                </div>
                                <div id="verifying" class="cb-container" style="display: none;">
                                    <div class="spinner-container">
                                        <svg id="spinner-i" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="unspun">
                                            <!-- SVG элементы спиннера -->
                                        </svg>
                                    </div>
                                    <div id="verifying-msg"><span id="verifying-text">Идет проверка...</span><br>
                                        <div id="error-overrun" class="error-message" style="display: none;"><span id="fr-overrun">Stuck here?</span><a href="#refresh" id="fr-overrun-link">Send Feedback</a></div>
                                    </div>
                                </div>
                                <div id="fail" class="cb-container" role="alert" style="display: none;">
                                    <svg id="fail-i" viewBox="0 0 30 30" aria-hidden="true" fill="none">
                                        <!-- SVG элементы ошибки -->
                                    </svg>
                                    <div id="failure-msg"><span id="fail-text">Сбой</span>
                                        <div id="having-trouble-message" class="error-message"><span id="fr-helper">Проблемы?</span><a href="javascript:location.reload();" id="fr-helper-link">Refresh</a></div>
                                    </div>
                                </div>
                                <div id="expired" class="cb-container" role="alert" style="display: none;">
                                    <svg id="expired-i" viewBox="0 0 30 30" aria-hidden="true">
                                        <!-- SVG элементы истекшей сессии -->
                                    </svg>
                                    <div id="expiry-msg">
                                        <p id="expired-text">Сессия устарела<span id="full-stop-expired-text">. </span><a href="#refresh" id="expired-refresh-link">Обновить</a></p>
                                    </div>
                                </div>
                                <div id="timeout" class="cb-container" role="alert" style="display: none;">
                                    <svg id="timeout-i" viewBox="0 0 30 30" aria-hidden="true">
                                        <!-- SVG элементы таймаута -->
                                    </svg>
                                    <div id="timeout-msg">
                                        <p id="timeout-text">Время истекло<span id="full-stop-timeout-text">. </span><a href="#refresh" id="timeout-refresh-link">Обновить</a></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                initCheckboxCaptcha();
                document.getElementById('uHkM6').style.display = 'block';
            }
        }

        function initSliderCaptcha() {
            const wrapper = document.getElementById('slider-wrapper');
            const thumb = document.getElementById('slider-thumb');
            const track = document.querySelector('.slider-track');
            const progress = document.querySelector('.slider-progress');
            const captcha = document.getElementById('slider-captcha');
            const loadingSpinner = document.getElementById('InsTY1');
            const successMessage = document.getElementById('tWuBw3');
            let isDragging = false;

            // Рандомный угол наклона
            const angle = (Math.random() * 10 + 20) * (Math.random() > 0.5 ? 1 : -1);
            wrapper.style.transform = `rotate(${angle}deg)`;

            // Начальная позиция
            let startX = 0;
            let thumbPosition = 0;
            const maxPosition = track.offsetWidth - thumb.offsetWidth;

            // Обработчики для мыши
            thumb.addEventListener('mousedown', (e) => {
                isDragging = true;
                startX = e.clientX - thumb.getBoundingClientRect().left;
                e.preventDefault();
            });

            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                
                let newPosition = e.clientX - track.getBoundingClientRect().left - startX;
                newPosition = Math.max(0, Math.min(maxPosition, newPosition));
                
                thumb.style.left = newPosition + 'px';
                thumbPosition = newPosition;
                
                const percent = Math.round((newPosition / maxPosition) * 100);
                progress.style.width = percent + '%';
            });

            document.addEventListener('mouseup', () => {
                if (!isDragging) return;
                isDragging = false;
                
                const percent = Math.round((thumbPosition / maxPosition) * 100);
                if (percent >= 90) {
                    onVerificationSuccess();
                } else {
                    resetSlider();
                }
            });

            // Обработчики для тач-устройств
            thumb.addEventListener('touchstart', (e) => {
                isDragging = true;
                startX = e.touches[0].clientX - thumb.getBoundingClientRect().left;
                e.preventDefault();
            });

            document.addEventListener('touchmove', (e) => {
                if (!isDragging) return;
                let newPosition = e.touches[0].clientX - track.getBoundingClientRect().left - startX;
                newPosition = Math.max(0, Math.min(maxPosition, newPosition));
                
                thumb.style.left = newPosition + 'px';
                thumbPosition = newPosition;
                
                const percent = Math.round((newPosition / maxPosition) * 100);
                progress.style.width = percent + '%';
            });

            document.addEventListener('touchend', () => {
                if (!isDragging) return;
                isDragging = false;
                
                const percent = Math.round((thumbPosition / maxPosition) * 100);
                if (percent >= 90) {
                    onVerificationSuccess();
                } else {
                    resetSlider();
                }
            });

            function resetSlider() {
                thumb.style.left = '0';
                progress.style.width = '0';
                thumbPosition = 0;
            }

            function onVerificationSuccess() {
                captcha.style.opacity = '0';
                captcha.style.transform = 'translateY(-20px)';
                captcha.style.transition = 'all 0.5s ease';
                
                loadingSpinner.style.display = 'block';
                
                setTimeout(() => {
                    captcha.style.display = 'none';
                    loadingSpinner.style.display = 'none';
                    successMessage.style.display = 'block';
                    
                    setTimeout(() => {
                        checkBot('set-marker');
                    }, 1000);
                }, 500);
            }
        }

        function initCheckboxCaptcha() {
            const checkbox = document.getElementById('uiEr3');
            const verifying = document.getElementById('verifying');
            const loadingSpinner = document.getElementById('InsTY1');
            const successMessage = document.getElementById('tWuBw3');

            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    verifying.style.display = 'grid';
                    
                    setTimeout(() => {
                        verifying.style.display = 'none';
                        successMessage.style.display = 'block';
                        
                        setTimeout(() => {
                            checkBot('set-marker');
                        }, 1000);
                    }, 2000);
                }
            });
        }
    </script>
    <div class="footer" role="contentinfo">
        <div class="footer-inner">
            <div class="clearfix diagnostic-wrapper">
                <div class="ray-id">Ray ID: <code><?= $this->Profile->RayID; ?></code></div>
            </div>
            <div class="text-center" id="footer-text">Производительность и безопасность на платформе <a
                    rel="noopener noreferer" href="https://github.com/githubniko/antibot"
                    target="_blank">AntibotWAF</a></div>
        </div>
    </div>
</body>

</html>
