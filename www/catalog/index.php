<?


$ajax = getallheaders()['Ajax'] ?? null;

if ($ajax) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

    if ($ajax === 'filter') {
        Components\SmartFilter::catalogList()->sendResponse();
    }

    if ($ajax === 'products') {
        Components\SmartFilter::catalogList();
        Components\CatalogSection::catalogList()->sendResponse();
    }
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Test");

Components\SmartFilter::catalogList()->render();
Components\SmartFilter::catalogList()->setTemplate('.default')->render();
Components\CatalogSection::catalogList()->render();

?><? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>