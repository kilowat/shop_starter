<?php
/**
 * Bootstrap AJAX-запроса.
 *
 * Подключается из страницы до header.php. Если это не AJAX-запрос —
 * просто возвращает управление странице. Если AJAX — подключает ядро
 * и конфигурацию действий страницы (по умолчанию _ajax.php рядом со скриптом).
 *
 * Переменные, которые можно задать до подключения:
 *   $ajaxActionField — имя POST-поля с действием (по умолчанию ajax_action);
 *   $ajaxConfigFile  — абсолютный путь к файлу конфигурации.
 */

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    return;
}

$__ajaxActionField = $ajaxActionField ?? 'ajax_action';

if (!is_string($__ajaxActionField) || $__ajaxActionField === '') {
    $__ajaxActionField = 'ajax_action';
}

$__ajaxAction = $_POST[$__ajaxActionField] ?? null;

if (!is_string($__ajaxAction) || $__ajaxAction === '') {
    unset($__ajaxActionField, $__ajaxAction);

    return;
}

$__ajaxScriptFile = (string)($_SERVER['SCRIPT_FILENAME'] ?? '');
$__ajaxScriptDir = $__ajaxScriptFile !== ''
    ? dirname($__ajaxScriptFile)
    : rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/');

$__ajaxConfigFile = $ajaxConfigFile ?? $__ajaxScriptDir . '/_ajax.php';

if (!is_file($__ajaxConfigFile)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo '{"status":"error","errors":[{"code":"invalid_config","message":"AJAX config not found"}]}';
    exit;
}

foreach ([
    'NO_KEEP_STATISTIC',
    'STOP_STATISTICS',
    'NO_AGENT_STATISTIC',
    'NO_AGENT_CHECK',
] as $__ajaxConstant) {
    if (!defined($__ajaxConstant)) {
        define($__ajaxConstant, true);
    }
}

unset($__ajaxConstant);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require $__ajaxConfigFile;

exit;
