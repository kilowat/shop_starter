<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

/** @var CMain $APPLICATION */

$APPLICATION->IncludeComponent(
    'bitrix:catalog.element',
    'shop_bootstrap',
    [
        'IBLOCK_TYPE' => 'catalog',
        'IBLOCK_ID' => 2,
        'ELEMENT_ID' => $_REQUEST['ELEMENT_ID'] ?? '',
        'ELEMENT_CODE' => $_REQUEST['ELEMENT_CODE'] ?? '',
        'SECTION_ID' => $_REQUEST['SECTION_ID'] ?? '',
        'SECTION_CODE' => $_REQUEST['SECTION_CODE'] ?? '',

        'PROPERTY_CODE' => ['ARTNUMBER', 'MANUFACTURER', 'MATERIAL'],
        'OFFERS_FIELD_CODE' => ['ID', 'CODE', 'NAME', 'SORT', 'PREVIEW_PICTURE', 'DETAIL_PICTURE'],
        'OFFERS_PROPERTY_CODE' => ['COLOR_REF', 'SIZES_CLOTHES', 'SIZES_SHOES'],
        'OFFER_TREE_PROPS' => ['COLOR_REF', 'SIZES_CLOTHES', 'SIZES_SHOES'],
        'OFFERS_LIMIT' => 0,

        'PRICE_CODE' => ['BASE'],
        'PRICE_VAT_INCLUDE' => 'Y',
        'SHOW_OLD_PRICE' => 'Y',
        'SHOW_DISCOUNT_PERCENT' => 'Y',
        'USE_PRICE_COUNT' => 'N',

        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => 3600,
        'CACHE_GROUPS' => 'Y',

        'SET_TITLE' => 'Y',
        'SET_BROWSER_TITLE' => 'Y',
        'SET_META_KEYWORDS' => 'Y',
        'SET_META_DESCRIPTION' => 'Y',
        'ADD_SECTIONS_CHAIN' => 'Y',

        'COMPATIBLE_MODE' => 'N',
        'DISABLE_INIT_JS_IN_COMPONENT' => 'Y',

        'SET_STATUS_404' => 'Y',
        'SHOW_404' => 'Y',
        'MESSAGE_404' => 'Товар не найден',
    ],
    false
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';