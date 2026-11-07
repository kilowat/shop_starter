<?php
/**
 * AJAX каталога.
 *
 * Здесь только конфигурация: какие include-файлы выполнить и что вернуть.
 * Универсальная обработка запроса — в local/include/ajax.php.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$resetFilter = static function (): void {
    $GLOBALS['arrFilter'] = [];
};

$ajaxConfig = [
    'method' => 'POST',
    'actionField' => 'catalog_action',
    'sessidField' => 'sessid',
    'actions' => [
        'list' => [
            'init' => $resetFilter,
            'prepare' => [
                '/local/include/components/catalog_sort.php',
                '/local/include/components/smart_filter.php',
            ],
            'render' => '/local/include/components/catalog_list.php',
            'response' => 'html',
        ],
        'filter' => [
            'init' => $resetFilter,
            'prepare' => [],
            'render' => '/local/include/components/smart_filter.php',
            'returnResult' => true,
            'response' => 'result',
            'resultKeys' => ['COUNTS', 'TOTAL'],
        ],
    ],
];

require $_SERVER['DOCUMENT_ROOT'] . '/local/include/ajax.php';
