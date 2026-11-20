<?php
/**
 * Ссылка пользователя в шапке — объявление shop:user.link.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CMain $APPLICATION */

$APPLICATION->IncludeComponent(
    'shop:user.link',
    '',
    [
        'PERSONAL_URL' => '/personal/',
        'PERSONAL_TEXT' => 'Личный кабинет',
        'AUTH_URL' => '/auth/?login=yes',
        'AUTH_TEXT' => 'Авторизоваться',
        'SHOW_NAME' => 'N',
    ],
    false,
    ['HIDE_ICONS' => 'N']
);