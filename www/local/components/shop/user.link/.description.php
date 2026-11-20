<?php
/**
 * Ссылка на личный кабинет (или авторизацию) с иконкой пользователя.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => 'Ссылка пользователя',
    'DESCRIPTION' => 'Выводит ссылку на личный кабинет авторизованному пользователю или на авторизацию гостю',
    'PATH' => [
        'ID' => 'shop',
        'NAME' => 'Shop',
    ],
];