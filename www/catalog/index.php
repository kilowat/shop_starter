<?php
/**
 * Страница каталога: весь AJAX обслуживает компонент shop:catalog.
 */

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->IncludeComponent('shop:catalog', '', [
    'IBLOCK_TYPE' => 'catalog',
    'IBLOCK_ID' => 2,
    'FILTER_NAME' => 'arrFilter',
    'PRICE_CODE' => ['BASE'],
    'PAGE_ELEMENT_COUNT' => 12,
    'CACHE_TIME' => 3600,

    // Сортировки можно не передавать — возьмётся стандартный набор.
    // Свой набор передаётся массивом (в визуальном редакторе — JSON-строкой).
    'SORT_FIELDS' => [
        ['CODE' => 'default', 'NAME' => 'По популярности', 'FIELD' => 'sort', 'ORDER' => 'asc'],
        ['CODE' => 'new', 'NAME' => 'Сначала новые', 'FIELD' => 'id', 'ORDER' => 'desc'],
        ['CODE' => 'price_asc', 'NAME' => 'Цена: по возрастанию', 'FIELD' => 'CATALOG_PRICE_1', 'ORDER' => 'asc'],
        ['CODE' => 'price_desc', 'NAME' => 'Цена: по убыванию', 'FIELD' => 'CATALOG_PRICE_1', 'ORDER' => 'desc'],
        ['CODE' => 'name_asc', 'NAME' => 'Название: А-Я', 'FIELD' => 'name', 'ORDER' => 'asc'],
        ['CODE' => 'name_desc', 'NAME' => 'Название: Я-А', 'FIELD' => 'name', 'ORDER' => 'desc'],
        ['CODE' => 'old', 'NAME' => 'Сначала старые', 'FIELD' => 'id', 'ORDER' => 'asc'],
    ],
    'SORT_DEFAULT' => 'default',
    'SORT_PARAM_NAME' => 'sort',
    'SORT_TITLE' => 'Сортировка',
]);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
