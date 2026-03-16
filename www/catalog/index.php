<?
$ajax = $_REQUEST['ajax'] ?? $_SERVER['ajax'] ?? null;

if ($ajax) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

    if ($ajax === 'filter') {
        Components\SmartFilter::forCatalogListJsonResponse()->send();
    }

    if ($ajax === 'products') {
        Components\SmartFilter::forCatalogList();
        Components\CatalogSection::forCatalogList()->sendResponse();
    }

    Bitrix\Main\Application::getInstance()->end();
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Test");

Components\SmartFilter::forCatalogList()->render();
Components\CatalogSection::forCatalogList()->render();

$fetch = CIBlockElement::GetByID(11)->Fetch();
$picId = $fetch['DETAIL_PICTURE'];
$preset = [
    'preivew' => [
        'width' => 400,
        'height' => 400,
        'min_width' => 600,
        'webp' => true
    ]
];
$res = Lib\Image\ImageHelper::get($picId, $preset);

$pic = Lib\Image\Picture::show($res);
echo ($pic);

?><? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
