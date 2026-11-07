<?php
/**
 * Параметры компонента shop:catalog.
 *
 * Вложенным компонентам (фильтр, сортировка, список) всё передаётся отсюда,
 * поэтому настройки каталога живут в одном месте.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** Типы цен: в PRICE_CODE передаётся NAME группы, он же код. */
$priceCodes = ['BASE' => 'BASE'];

if (\Bitrix\Main\Loader::includeModule('catalog')) {
    $groups = \Bitrix\Catalog\GroupTable::getList([
        'select' => ['NAME', 'SORT'],
        'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
    ])->fetchAll();

    if ($groups) {
        $priceCodes = [];

        foreach ($groups as $group) {
            $name = (string)($group['NAME'] ?? '');

            if ($name !== '') {
                $priceCodes[$name] = $name;
            }
        }
    }
}

$arComponentParameters = [
    'PARAMETERS' => [
        'IBLOCK_TYPE' => [
            'PARENT' => 'BASE',
            'NAME' => 'Тип инфоблока',
            'TYPE' => 'STRING',
            'DEFAULT' => 'catalog',
        ],
        'IBLOCK_ID' => [
            'PARENT' => 'BASE',
            'NAME' => 'ID инфоблока',
            'TYPE' => 'STRING',
            'DEFAULT' => '2',
        ],
        'FILTER_NAME' => [
            'PARENT' => 'BASE',
            'NAME' => 'Имя переменной фильтра',
            'TYPE' => 'STRING',
            'DEFAULT' => 'arrFilter',
        ],
        'PRICE_CODE' => [
            'PARENT' => 'BASE',
            'NAME' => 'Типы цен',
            'TYPE' => 'LIST',
            'MULTIPLE' => 'Y',
            'VALUES' => $priceCodes,
            'DEFAULT' => ['BASE'],
        ],
        'PAGE_ELEMENT_COUNT' => [
            'PARENT' => 'BASE',
            'NAME' => 'Товаров на странице',
            'TYPE' => 'STRING',
            'DEFAULT' => '12',
        ],
        'SORT_FIELDS' => [
            'PARENT' => 'BASE',
            'NAME' => 'Варианты сортировки (JSON; пусто — стандартный набор)',
            'TYPE' => 'STRING',
            'ROWS' => 10,
            'COLS' => 60,
            'DEFAULT' => '',
        ],
        'SORT_DEFAULT' => [
            'PARENT' => 'BASE',
            'NAME' => 'Сортировка по умолчанию (CODE)',
            'TYPE' => 'STRING',
            'DEFAULT' => 'default',
        ],
        'SORT_PARAM_NAME' => [
            'PARENT' => 'BASE',
            'NAME' => 'Имя GET-параметра сортировки',
            'TYPE' => 'STRING',
            'DEFAULT' => 'sort',
        ],
        'SORT_TITLE' => [
            'PARENT' => 'BASE',
            'NAME' => 'Заголовок сортировки',
            'TYPE' => 'STRING',
            'DEFAULT' => 'Сортировка',
        ],
        'CACHE_TIME' => [
            'PARENT' => 'CACHE_SETTINGS',
            'DEFAULT' => 3600,
        ],
    ],
];
