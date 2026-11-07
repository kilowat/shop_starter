<?php
/**
 * AJAX каталога.
 *
 * Подключается из catalog/index.php после prolog_before.php.
 * Настройки компонентов берутся из тех же include-файлов, что и на странице,
 * поэтому клиент не может передать свои IBLOCK_ID, шаблон, FIELDS и т.п.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CMain $APPLICATION */

$respond = static function (array $payload, int $status = 200): void {
    global $APPLICATION;

    $APPLICATION->RestartBuffer();

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Content-Type-Options: nosniff');

    echo \Bitrix\Main\Web\Json::encode($payload, JSON_UNESCAPED_UNICODE);

    die();
};

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        $respond([
            'status' => 'error',
            'errors' => [['code' => 'invalid_method', 'message' => 'Method not allowed']],
        ], 405);
    }

    $action = $_POST['catalog_action'] ?? '';
    $action = is_string($action) ? $action : '';

    if (!in_array($action, ['list', 'filter'], true)) {
        $respond([
            'status' => 'error',
            'errors' => [['code' => 'invalid_action', 'message' => 'Unknown action']],
        ], 400);
    }

    if (!check_bitrix_sessid()) {
        $respond([
            'status' => 'error',
            'errors' => [['code' => 'invalid_sessid', 'message' => 'Session expired']],
        ], 403);
    }

    $docRoot = rtrim((string)$_SERVER['DOCUMENT_ROOT'], '/');
    $hideIcons = 'Y';

    if ($action === 'list') {
        ob_start();
        require $docRoot . '/local/include/components/catalog_sort.php';
        ob_end_clean();

        $GLOBALS['arrFilter'] = [];
        $returnResult = false;

        ob_start();
        require $docRoot . '/local/include/components/smart_filter.php';
        ob_end_clean();

        ob_start();
        require $docRoot . '/local/include/components/catalog_list.php';
        $html = ob_get_clean();

        $respond([
            'status' => 'success',
            'data' => ['html' => $html],
        ]);
    }

    $GLOBALS['arrFilter'] = [];
    $returnResult = true;

    ob_start();
    require $docRoot . '/local/include/components/smart_filter.php';
    ob_end_clean();

    $result = is_array($catalogFilterResult ?? null) ? $catalogFilterResult : [];

    $respond([
        'status' => 'success',
        'data' => [
            'result' => [
                'COUNTS' => $result['COUNTS'] ?? [],
                'TOTAL' => (int)($result['TOTAL'] ?? 0),
            ],
        ],
    ]);
} catch (\Throwable $e) {
    AddMessage2Log(
        $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
        'catalog.ajax'
    );

    $respond([
        'status' => 'error',
        'errors' => [['code' => 'render_failed', 'message' => 'Unable to render catalog']],
    ], 500);
}
