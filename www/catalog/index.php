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
        "width" => 400,
        "height" => 300,
        "resize_type" => BX_RESIZE_IMAGE_PROPORTIONAL_ALT,
        "quality" => 85, // 🔥 одно поле
    ],
    [
        "class" => "img-fluid"
    ]
];
$res = Lib\Image\ImageHelper::get($picId, $preset);
//var_dump($res);
$pic = Lib\Image\Picture::show($res['resizes']['preivew']);
echo ($pic);

?><? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
