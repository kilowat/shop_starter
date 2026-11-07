<?php
/**
 * Параметры компонента сортировки.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$defaultFields = [
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
];

$arComponentParameters = [
    'PARAMETERS' => [
        'FIELDS' => [
            'NAME' => 'Поля сортировки (JSON)',
            'TYPE' => 'STRING',
            'ROWS' => 8,
            'COLS' => 60,
            'DEFAULT' => json_encode($defaultFields, JSON_UNESCAPED_UNICODE),
        ],
        'DEFAULT' => [
            'NAME' => 'Код сортировки по умолчанию',
            'TYPE' => 'STRING',
            'DEFAULT' => 'default',
        ],
        'PARAM_NAME' => [
            'NAME' => 'Имя параметра URL',
            'TYPE' => 'STRING',
            'DEFAULT' => 'sort',
        ],
        'TITLE' => [
            'NAME' => 'Заголовок',
            'TYPE' => 'STRING',
            'DEFAULT' => 'Сортировка',
        ],
        'SHOW_TITLE' => [
            'NAME' => 'Показывать заголовок',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ],
    ],
];
