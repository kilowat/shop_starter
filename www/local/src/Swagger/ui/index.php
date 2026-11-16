<?php
define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('BX_SECURITY_SESSION_READONLY', true);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

$csrf = bitrix_sessid();
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>API Documentation</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css">
    <style>
        #captcha-panel {
            position: sticky;
            top: 0;
            z-index: 1000;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            padding: 8px 14px;
            background: #1b1b1b;
            color: #fff;
            font: 13px/1.4 sans-serif;
            border-bottom: 2px solid #4990e2;
        }

        #captcha-panel label {
            display: flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
        }

        #captcha-image {
            height: 38px;
            min-width: 100px;
            background: #fff;
            border: 1px solid #555;
            border-radius: 3px;
            cursor: pointer;
        }

        #captcha-word {
            width: 130px;
            padding: 6px 8px;
            border: 1px solid #555;
            border-radius: 3px;
            background: #fff;
            color: #222;
        }

        #captcha-refresh {
            padding: 6px 10px;
            border: 0;
            border-radius: 3px;
            background: #4990e2;
            color: #fff;
            cursor: pointer;
        }

        #captcha-status {
            color: #9fd49f;
        }
    </style>
</head>

<body>
    <div id="captcha-panel">
        <strong>Капча</strong>
        <img id="captcha-image" src="" alt="Нажмите, чтобы обновить капчу" title="Нажмите, чтобы обновить капчу">
        <input id="captcha-word" type="text" autocomplete="off" placeholder="код с картинки">
        <button id="captcha-refresh" type="button">Новая капча</button>
        <label>
            <input id="captcha-autofill" type="checkbox" checked>
            подставлять в запросы
        </label>
        <span id="captcha-status"></span>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
    <script>
        const CSRF_TOKEN = <?= \Bitrix\Main\Web\Json::encode($csrf) ?>;
        const CAPTCHA_ENDPOINT = '/api/auth/captcha';
        const CAPTCHA_ACTIONS = [
            '/api/auth/login',
            '/api/auth/password/forgot',
            '/api/auth/password/reset',
        ];

        const captchaImage = document.getElementById('captcha-image');
        const captchaWord = document.getElementById('captcha-word');
        const captchaRefresh = document.getElementById('captcha-refresh');
        const captchaAutofill = document.getElementById('captcha-autofill');
        const captchaStatus = document.getElementById('captcha-status');

        const captcha = {
            sid: '',
            word: '',
            injected: false,
        };

        function setCaptchaStatus(message) {
            captchaStatus.textContent = message;
        }

        function applyCaptcha(sid, url) {
            captcha.sid = String(sid);
            captcha.word = '';
            captcha.injected = false;
            captchaWord.value = '';

            const imageUrl = url || `/bitrix/tools/captcha.php?captcha_sid=${encodeURIComponent(captcha.sid)}`;
            const separator = imageUrl.includes('?') ? '&' : '?';
            captchaImage.src = `${imageUrl}${separator}t=${Date.now()}`;
        }

        async function loadCaptcha(customData) {
            if (customData && customData.captcha_sid) {
                applyCaptcha(customData.captcha_sid, customData.captcha_url);
                setCaptchaStatus('Получена новая капча — введите код');
                captchaWord.focus();
                return;
            }

            setCaptchaStatus('Загружаем капчу…');

            try {
                const response = await fetch(CAPTCHA_ENDPOINT, {
                    credentials: 'include',
                    cache: 'no-store',
                    headers: {
                        'X-Bitrix-Csrf-Token': CSRF_TOKEN,
                    },
                });
                const payload = await response.json();
                const data = payload.data || payload.result || {};

                if (!data.captcha_sid) {
                    throw new Error(payload.errors?.[0]?.message || 'капча не получена');
                }

                applyCaptcha(data.captcha_sid, data.captcha_url);
                setCaptchaStatus('');
            } catch (error) {
                setCaptchaStatus('Ошибка капчи: ' + error.message);
            }
        }

        function isCaptchaAction(url) {
            try {
                return CAPTCHA_ACTIONS.includes(new URL(url, window.location.href).pathname);
            } catch {
                return false;
            }
        }

        function getResponsePayload(response) {
            if (response.obj && typeof response.obj === 'object') {
                return response.obj;
            }

            if (typeof response.text === 'string') {
                try {
                    return JSON.parse(response.text);
                } catch {
                    return null;
                }
            }

            return null;
        }

        captchaWord.addEventListener('input', () => {
            captcha.word = captchaWord.value.trim();
        });

        captchaImage.addEventListener('click', () => loadCaptcha());
        captchaRefresh.addEventListener('click', () => loadCaptcha());

        loadCaptcha();

        SwaggerUIBundle({
            url: '/swagger/openapi.json',
            dom_id: '#swagger-ui',
            requestInterceptor: (req) => {
                req.headers['X-Bitrix-Csrf-Token'] = CSRF_TOKEN;
                req.credentials = 'include';

                captcha.injected = false;

                if (
                    captchaAutofill.checked
                    && captcha.word !== ''
                    && captcha.sid !== ''
                    && String(req.method).toUpperCase() === 'POST'
                    && isCaptchaAction(req.url)
                ) {
                    try {
                        const body = typeof req.body === 'string' ? JSON.parse(req.body) : req.body;

                        if (body && typeof body === 'object') {
                            body.captcha_word = captcha.word;
                            body.captcha_sid = captcha.sid;
                            req.body = JSON.stringify(body);
                            captcha.injected = true;
                        }
                    } catch {
                        captcha.injected = false;
                    }
                }

                return req;
            },
            responseInterceptor: (response) => {
                const payload = getResponsePayload(response);
                const errors = Array.isArray(payload?.errors) ? payload.errors : [];
                const captchaError = errors.find((error) => error?.code === 'captcha_required');

                if (captchaError) {
                    loadCaptcha(captchaError.customData);
                } else if (captcha.injected) {
                    loadCaptcha();
                }

                return response;
            },
        });
    </script>
</body>

</html>