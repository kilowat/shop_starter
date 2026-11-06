<?php
/**
 * Параметры шаблона списка — редактируются в визуальном редакторе компонента.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arTemplateParameters = [
    'LIST_URL' => [
        'NAME' => 'URL обновления списка (ajax)',
        'TYPE' => 'STRING',
        'DEFAULT' => '/api/catalog/list',
    ],
];
