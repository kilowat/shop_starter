<?php
/**
 * Список товаров каталога — объявление bitrix:catalog.section (шаблон shop_bootstrap).
 *
 * Фильтр берётся из $GLOBALS['arrFilter']: его заполняет смарт-фильтр
 * (catalog.filter.php), поэтому фильтр подключают раньше списка.
 * Сортировку, как и фильтр, задаёт отдельный компонент (catalog.sort.php):
 * пишет её в $GLOBALS['catalogSort'] (параметр SORT_NAME). Если сортировку
 * не подключали, остаётся стандартная: sort asc / id desc.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CMain $APPLICATION */

// Имя переменной совпадает с SORT_NAME в catalog.sort.php.
$sort = is_array($GLOBALS['arrSort'] ?? null) ? $GLOBALS['arrSort'] : [];

$APPLICATION->IncludeComponent(
    'bitrix:catalog.section',
    'shop_bootstrap',
    [
        'IBLOCK_TYPE' => 'catalog',
        'IBLOCK_ID' => 2,
        'SECTION_ID' => '',
        'SECTION_CODE' => '',
        'FILTER_NAME' => 'arrFilter',
        'PRICE_CODE' => ['BASE'],
        'PAGE_ELEMENT_COUNT' => 12,
        'CACHE_TIME' => 3600,
        'CACHE_TYPE' => 'A',
        'CACHE_GROUPS' => 'Y',
        'CACHE_FILTER' => 'Y',

        'SHOW_ALL_WO_SECTION' => 'Y',
        'INCLUDE_SUBSECTIONS' => 'Y',
        'PRICE_VAT_INCLUDE' => 'Y',
        'CONVERT_CURRENCY' => 'N',
        'LINE_ELEMENT_COUNT' => '3',

        'ELEMENT_SORT_FIELD' => $sort['FIELD'] ?? 'sort',
        'ELEMENT_SORT_ORDER' => $sort['ORDER'] ?? 'asc',
        'ELEMENT_SORT_FIELD2' => $sort['FIELD2'] ?? 'id',
        'ELEMENT_SORT_ORDER2' => $sort['ORDER2'] ?? 'desc',

        'PROPERTY_CODE' => [],
        'OFFERS_LIMIT' => '5',
        'DETAIL_URL' => '/catalog/#SECTION_CODE#/#ELEMENT_CODE#/',
        'SECTION_URL' => '/catalog/#SECTION_CODE#/',
        'SHOW_OLD_PRICE' => 'Y',
        'SHOW_DISCOUNT_PERCENT' => 'Y',
        'USE_PRICE_COUNT' => 'N',
        'PRODUCT_SUBSCRIPTION' => 'N',
        'DISPLAY_COMPARE' => 'N',
        'ADD_PICT_PROP' => '-',
        'LABEL_PROP' => '-',
        'ADD_SECTIONS_CHAIN' => 'N',
        'SET_TITLE' => 'N',
        'SET_BROWSER_TITLE' => 'N',
        'SET_META_KEYWORDS' => 'N',
        'SET_META_DESCRIPTION' => 'N',
        'SET_LAST_MODIFIED' => 'N',
        'META_KEYWORDS' => '-',
        'META_DESCRIPTION' => '-',
        'BROWSER_TITLE' => '-',
        'COMPATIBLE_MODE' => 'N',
        'DISABLE_INIT_JS_IN_COMPONENT' => 'Y',

        // Шаблон bitrix:system.pagenavigation: classic | show_more | lazy
        'PAGER_TEMPLATE' => 'lazy',
        'PAGER_TITLE' => 'Товары',
        'PAGER_SHOW_ALWAYS' => 'N',
        'DISPLAY_TOP_PAGER' => 'N',
        'DISPLAY_BOTTOM_PAGER' => 'Y',
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);
