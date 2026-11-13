<?php
/**
 * Сортировка каталога — объявление shop:catalog.sort.
 *
 * Варианты сортировки и подпись селекта живут в одном месте — здесь.
 * FIELDS — обычный массив полей (без JSON). Как смарт-фильтр, компонент
 * ничего не возвращает, а пишет выбранную сортировку в глобальную переменную
 * $GLOBALS['catalogSort'] (параметр SORT_NAME). Список товаров берёт оттуда
 * FIELD/ORDER для catalog.section.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CMain $APPLICATION */

$APPLICATION->IncludeComponent(
    'shop:catalog.sort',
    '',
    [
        'FIELDS' => [
            ['CODE' => 'default', 'NAME' => 'По популярности', 'FIELD' => 'sort', 'ORDER' => 'asc'],
            ['CODE' => 'new', 'NAME' => 'Сначала новые', 'FIELD' => 'id', 'ORDER' => 'desc'],
            ['CODE' => 'price_asc', 'NAME' => 'Сначала дешевле', 'FIELD' => 'catalog_PRICE_1', 'ORDER' => 'asc'],
            ['CODE' => 'price_desc', 'NAME' => 'Сначала дороже', 'FIELD' => 'catalog_PRICE_1', 'ORDER' => 'desc'],
            ['CODE' => 'name_asc', 'NAME' => 'По названию А—Я', 'FIELD' => 'name', 'ORDER' => 'asc'],
            ['CODE' => 'name_desc', 'NAME' => 'По названию Я—А', 'FIELD' => 'name', 'ORDER' => 'desc'],
            ['CODE' => 'views', 'NAME' => 'По просмотрам', 'FIELD' => 'show_counter', 'ORDER' => 'desc'],
        ],
        'DEFAULT' => 'default',
        'PARAM_NAME' => 'sort',
        'SORT_NAME' => 'catalogSort',
        'TITLE' => 'Сортировка',
        'SHOW_TITLE' => 'Y',
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);
