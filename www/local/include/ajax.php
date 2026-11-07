<?php
/**
 * Универсальный AJAX-обработчик.
 *
 * Подключается после prolog_before.php. Конфигурацию передаёт страница:
 *
 * $ajaxConfig = [
 *     'method'      => 'POST',
 *     'actionField' => 'ajax_action',
 *     'sessidField' => 'sessid',
 *     'actions'     => [
 *         'list' => [
 *             'init'         => callable,       // необязательно
 *             'prepare'      => ['/local/include/components/....php'],
 *             'render'       => '/local/include/components/....php',
 *             'returnResult' => false,
 *             'response'     => 'html',         // html|result
 *             'resultKeys'   => ['KEY_A', 'KEY_B'],
 *         ],
 *     ],
 * ];
 *
 * Пути — от корня сайта. Render-файл может вернуть arResult через return.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CMain $APPLICATION */
/** @var array $ajaxConfig */

$respond = static function (array $payload, int $status = 200): void {
    global $APPLICATION;

    $APPLICATION->RestartBuffer();

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');

    echo \Bitrix\Main\Web\Json::encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );

    die();
};

try {
    if (!is_array($ajaxConfig ?? null) || !is_array($ajaxConfig['actions'] ?? null) || $ajaxConfig['actions'] === []) {
        $respond([
            'status' => 'error',
            'errors' => [['code' => 'invalid_config', 'message' => 'AJAX actions are not configured']],
        ], 500);
    }

    $method = strtoupper((string)($ajaxConfig['method'] ?? 'POST'));

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        $respond([
            'status' => 'error',
            'errors' => [['code' => 'invalid_method', 'message' => 'Method not allowed']],
        ], 405);
    }

    $sessidField = trim((string)($ajaxConfig['sessidField'] ?? ''));
    if ($sessidField === '') {
        $sessidField = 'sessid';
    }

    if (!check_bitrix_sessid($sessidField)) {
        $respond([
            'status' => 'error',
            'errors' => [['code' => 'invalid_sessid', 'message' => 'Session expired']],
        ], 403);
    }

    $actionField = trim((string)($ajaxConfig['actionField'] ?? ''));
    if ($actionField === '') {
        $actionField = 'action';
    }

    $actionName = $_POST[$actionField] ?? '';
    if (!is_string($actionName)) {
        $actionName = '';
    }

    $actions = $ajaxConfig['actions'];

    if ($actionName === '' || !isset($actions[$actionName]) || !is_array($actions[$actionName])) {
        $respond([
            'status' => 'error',
            'errors' => [['code' => 'invalid_action', 'message' => 'Unknown action']],
        ], 400);
    }

    $action = $actions[$actionName];

    if (!empty($action['init']) && is_callable($action['init'])) {
        call_user_func($action['init']);
    }

    $docRoot = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/');

    $resolvePath = static function (string $path) use ($docRoot): string {
        $path = trim($path);

        if ($path === '') {
            return '';
        }

        return str_starts_with($path, '/') ? $docRoot . $path : $path;
    };

    $includeFile = static function (string $path) use ($resolvePath): string {
        $file = $resolvePath($path);

        if ($file === '' || !is_file($file)) {
            throw new RuntimeException('AJAX include not found: ' . $path);
        }

        return $file;
    };

    $hideIcons = 'Y';

    $prepare = $action['prepare'] ?? [];
    if (!is_array($prepare)) {
        $prepare = [$prepare];
    }

    foreach ($prepare as $path) {
        if (!is_string($path) || $path === '') {
            continue;
        }

        $file = $includeFile($path);

        ob_start();
        require $file;
        ob_end_clean();
    }

    $renderPath = (string)($action['render'] ?? '');
    $renderFile = $includeFile($renderPath);

    $returnResult = !empty($action['returnResult']);

    ob_start();
    $result = require $renderFile;
    $html = ob_get_clean();

    $responseType = (string)($action['response'] ?? 'html');

    if ($responseType === 'html') {
        $respond([
            'status' => 'success',
            'data' => ['html' => $html],
        ]);
    }

    if ($responseType === 'result') {
        $keys = $action['resultKeys'] ?? [];
        if (!is_array($keys)) {
            $keys = [];
        }

        $data = [];

        if (is_array($result)) {
            if ($keys === []) {
                $data = $result;
            } else {
                foreach ($keys as $key) {
                    if (is_string($key) && array_key_exists($key, $result)) {
                        $data[$key] = $result[$key];
                    }
                }
            }
        }

        $respond([
            'status' => 'success',
            'data' => ['result' => $data],
        ]);
    }

    throw new RuntimeException('Unknown AJAX response type: ' . $responseType);
} catch (\Throwable $e) {
    AddMessage2Log(
        $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
        'ajax'
    );

    $respond([
        'status' => 'error',
        'errors' => [['code' => 'render_failed', 'message' => 'Unable to render response']],
    ], 500);
}
