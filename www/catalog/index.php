<?
$ajax = $_REQUEST['ajax'] ?? $_SERVER['ajax'] ?? null;

if ($ajax) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

    if ($ajax === 'filter') {
        Components\SmartFilter::forCatalogList()->sendDataResponse();
    }

    if ($ajax === 'products') {
        Components\SmartFilter::forCatalogList();
        Components\CatalogSection::forCatalogList()->sendHtmlResponse();
    }

    Bitrix\Main\Application::getInstance()->end();
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Test");

Components\SmartFilter::forCatalogList()->renderWebComponent();
Components\CatalogSection::forCatalogList()->render();

?><? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>