<?php
/**
 * Сортировка каталога: серверный <select>, состояние — в URL и в
 * глобальной переменной SORT_NAME (по умолчанию catalogSort).
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentDescription = [
    'NAME' => 'Сортировка каталога',
    'DESCRIPTION' => 'Выводит select сортировки и пишет выбор в URL и глобальную переменную SORT_NAME',
    'PATH' => [
        'ID' => 'shop',
        'NAME' => 'Shop',
    ],
];
