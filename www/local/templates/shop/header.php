<?php
/**
 * Шапка шаблона shop.
 * CSS подключается функцией проекта addAssetCss() из local/src/func.php.
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

addAssetCss([
    'css/bootstrap/bootstrap.min.css',
    'css/style.css',
]);

/** @var CMain $APPLICATION */
?><!DOCTYPE html>
<html lang="ru">

<head>
    <?php $APPLICATION->ShowHead(); ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php $APPLICATION->ShowTitle(); ?></title>
</head>

<body class="d-flex flex-column min-vh-100 bg-light">

    <?php $APPLICATION->ShowPanel(); ?>

    <header class="bg-white border-bottom">
        <nav class="navbar navbar-expand-lg bg-white">
            <div class="container">
                <a class="navbar-brand fw-semibold" href="/">Shop Starter</a>
                <?php $APPLICATION->IncludeComponent(
                    'bitrix:menu',
                    'shop_nav',
                    [
                        'ROOT_MENU_TYPE' => 'shop',
                        'MAX_LEVEL' => '1',
                        'CHILD_MENU_TYPE' => 'shop',
                        'USE_EXT' => 'N',
                        'DELAY' => 'N',
                        'ALLOW_MULTILEVEL' => 'N',
                        'MENU_CACHE_TYPE' => 'A',
                        'MENU_CACHE_TIME' => '3600',
                        'MENU_CACHE_USE_GROUPS' => 'Y',
                        'MENU_CACHE_GET_VARS' => [],
                    ],
                    false
                ); ?>
            </div>
        </nav>
    </header>

    <main class="flex-grow-1 py-4">