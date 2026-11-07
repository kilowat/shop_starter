<?php
/**
 * Смарт-фильтр каталога.
 *
 * Файл используется и страницей каталога, и AJAX-обработчиком,
 * поэтому настройки компонента задаются здесь один раз.
 *
 * @var CMain $APPLICATION
 * @var bool  $returnResult
 * @var bool  $hideIcons
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$catalogFilterResult = $APPLICATION->IncludeComponent(
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
    ['HIDE_ICONS' => $hideIcons ?? 'N'],
    $returnResult ?? false
);
