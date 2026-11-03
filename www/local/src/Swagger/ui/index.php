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
</head>

<body>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
    <script>
        const CSRF_TOKEN = <?= \Bitrix\Main\Web\Json::encode($csrf) ?>;

        SwaggerUIBundle({
            url: '/swagger/openapi.json',
            dom_id: '#swagger-ui',
            requestInterceptor: (req) => {
                req.headers['X-Bitrix-Csrf-Token'] = CSRF_TOKEN;
                req.credentials = 'include'; // нужна сессионная cookie
                return req;
            }
        });
    </script>
</body>

</html>