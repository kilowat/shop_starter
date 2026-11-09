<?php
/**
 * Параметры компонента сортировки.
 *
 * FIELDS — обычный массив с полями сортировки. Он передаётся из кода
 * (local/include/components/catalog.sort.php), поэтому в форме настроек
 * его нет и JSON не используется.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
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
        'SORT_NAME' => [
            'NAME' => 'Имя глобальной переменной',
            'TYPE' => 'STRING',
            'DEFAULT' => 'arrSort',
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
