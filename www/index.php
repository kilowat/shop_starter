<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->SetTitle("О компании");
?>
<main class="container">
    <h1><?php $APPLICATION->ShowTitle(); ?></h1>
    <? Components\CatalogSection::forCatalogList()->render() ?>
    <p>
        Это простая страница шаблона Bitrix.
    </p>
    <p>
        Здесь может быть любой HTML-контент.
    </p>
</main>


<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>