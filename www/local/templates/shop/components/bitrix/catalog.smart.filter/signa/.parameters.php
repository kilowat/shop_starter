<?php
/**
 * Параметры шаблона фильтра — редактируются в визуальном редакторе компонента.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arTemplateParameters = [
    'COUNTS_URL' => [
        'NAME' => 'URL пересчёта счётчиков (ajax)',
        'TYPE' => 'STRING',
        'DEFAULT' => '/api/catalog/filter',
    ],
];
