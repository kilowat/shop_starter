<?
Bitrix\Main\Page\Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/lib/signa/signa.min.js");
Bitrix\Main\Page\Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/web-components.js");
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <?php $APPLICATION->ShowHead(); ?>
    <title>
        <?php $APPLICATION->ShowTitle(); ?>
    </title>
    <meta charset="utf-8">
</head>

<body>

    <?php $APPLICATION->ShowPanel(); ?>

    <header>
        <div class="container">
            <a href="/">Мой сайт</a>
        </div>
    </header>