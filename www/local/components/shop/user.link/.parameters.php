<?php
/**
 * Параметры компонента ссылки пользователя.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'PARAMETERS' => [
        'PERSONAL_URL' => [
            'NAME' => 'Адрес личного кабинета',
            'TYPE' => 'STRING',
            'DEFAULT' => '/personal/',
        ],
        'PERSONAL_TEXT' => [
            'NAME' => 'Подпись личного кабинета',
            'TYPE' => 'STRING',
            'DEFAULT' => 'Личный кабинет',
        ],
        'AUTH_URL' => [
            'NAME' => 'Адрес авторизации',
            'TYPE' => 'STRING',
            'DEFAULT' => '/auth/?login=yes',
        ],
        'AUTH_TEXT' => [
            'NAME' => 'Подпись авторизации',
            'TYPE' => 'STRING',
            'DEFAULT' => 'Авторизоваться',
        ],
        'SHOW_NAME' => [
            'NAME' => 'Показывать имя пользователя',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'N',
        ],
    ],
];