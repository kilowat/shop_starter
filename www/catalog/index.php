<?

if ($_REQUEST['ajax'] === 'Y') {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
    Components\SmartFilter::forCatalogList();
    die();
}

if ($_REQUEST['ajax'] === 'products' || $_SERVER['ajax'] === 'products') {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
    Components\SmartFilter::forCatalogList();
    Components\CatalogSection::forCatalogList()->sendResponse();
    die();
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Test");

Components\SmartFilter::forCatalogList()->render();
Components\CatalogSection::forCatalogList()->render();

?><? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>