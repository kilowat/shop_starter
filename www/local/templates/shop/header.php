<?php

addAssetCss([
    'lib/bootstrap/bootstrap.min.css',
]);

addAssetJs([
    'lib/signa/dist/signa.min.js',
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
        <div class="container">
            <div class="row g-4">

                <?php $APPLICATION->AddBufferContent(function () use ($APPLICATION) {

                    $sidebar = trim($APPLICATION->GetViewContent('sidebar'));

                    if ($sidebar === '') {
                        return '';
                    }

                    ob_start(); ?>

                    <aside class="col-12 col-lg-3">
                        <div class="catalog-sidebar">
                            <?= $sidebar ?>
                        </div>
                    </aside>

                    <?php return ob_get_clean();
                }); ?>

                <div class="col-12 col-lg">