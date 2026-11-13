<?php
/**
 * Смарт-фильтр каталога — объявление bitrix:catalog.smart.filter (шаблон signa).
 *
 * Выводит HTML фильтра и возвращает arResult компонента (при ajax=y этот же
 * arResult отдаёт шаблон signa/ajax.php — только счётчики). Попутно заполняет
 * $GLOBALS['arrFilter'], по которому выбирает товары список
 * (catalog.section.php), поэтому фильтр подключают раньше списка.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CMain $APPLICATION */

$result = $APPLICATION->IncludeComponent(
    'bitrix:catalog.smart.filter',
    'signa',
    [
        'IBLOCK_TYPE' => 'catalog',
        'IBLOCK_ID' => 2,
        'SECTION_ID' => '',
        'FILTER_NAME' => 'arrFilter',
        'PRICE_CODE' => ['BASE'],
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '36000000',
        'CACHE_GROUPS' => 'Y',
        'SAVE_IN_SESSION' => 'N',
        'DISPLAY_ELEMENT_COUNT' => 'Y',
        'XML_EXPORT' => 'N',
        'CONVERT_CURRENCY' => 'N',
        'HIDE_NOT_AVAILABLE' => 'N',
    ],
    false,
    ['HIDE_ICONS' => 'Y'],
    true
);

return is_array($result) ? $result : [];
