<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Меню шапки: пункты «Главная» и «Каталог».
 * Подключается компонентом bitrix:menu (ROOT_MENU_TYPE = shop).
 */
$aMenuLinks = [
    ['Главная', '/', [], [], ''],
    ['Каталог', '/catalog/', [], [], ''],
];
