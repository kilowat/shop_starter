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

?><? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>


[
    'catalog_list' => [
        'img' => [
                'srcset' => [
                'width' => 800,
                'height' => 600,
            ] // или путь до файла    
            'src' => [
                    'width' => 800,
                    'height' => 600,
                    'exact' => false,
            ],
            'preview' => [
                    'width' => 200,
                    'height' => 200,
                    'exact' => false 
            ],
            'medium' => [
                    'width' => 400,
                    'height' => 400,
                    'exact' => false 
            ],   
        ]  
    ]
]