<?php
/**
 * Сортировка каталога.
 *
 * Компонент shop:catalog.sort сам разбирает FIELDS, определяет текущий
 * код из запроса и отдаёт готовые поля сортировки в $catalogSort.
 * Список (catalog_list.php) читает $catalogSort['SORT'].
 *
 * FIELDS передаётся JSON-строкой: так параметр нормально редактируется
 * в визуальном редакторе (компонент принимает и массив, и JSON).
 *
 * @var CMain $APPLICATION
 * @var bool  $hideIcons
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$sortFields = [
    [
        'CODE' => 'default',
        'NAME' => 'По популярности',
        'FIELD' => 'sort',
        'ORDER' => 'asc',
    ],
    [
        'CODE' => 'new',
        'NAME' => 'Сначала новые',
        'FIELD' => 'id',
        'ORDER' => 'desc',
    ],
    [
        'CODE' => 'price_asc',
        'NAME' => 'Цена: по возрастанию',
        'FIELD' => 'CATALOG_PRICE_1',
        'ORDER' => 'asc',
    ],
    [
        'CODE' => 'price_desc',
        'NAME' => 'Цена: по убыванию',
        'FIELD' => 'CATALOG_PRICE_1',
        'ORDER' => 'desc',
    ],
    [
        'CODE' => 'name_asc',
        'NAME' => 'Название: А-Я',
        'FIELD' => 'name',
        'ORDER' => 'asc',
    ],
    [
        'CODE' => 'name_desc',
        'NAME' => 'Название: Я-А',
        'FIELD' => 'name',
        'ORDER' => 'desc',
    ],
    [
        'CODE' => 'old',
        'NAME' => 'Сначала старые',
        'FIELD' => 'id',
        'ORDER' => 'asc',
    ],
];

$catalogSort = $APPLICATION->IncludeComponent(
    'shop:catalog.sort',
    '',
    [
        'FIELDS' => json_encode($sortFields, JSON_UNESCAPED_UNICODE),
        'DEFAULT' => 'default',
        'PARAM_NAME' => 'sort',
        'TITLE' => 'Сортировка',
        'SHOW_TITLE' => 'Y',
    ],
    false,
    ['HIDE_ICONS' => $hideIcons ?? 'N'],
    true
);
