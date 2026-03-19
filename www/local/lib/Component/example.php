<?php

// ============================================================
// 1. СОЗДАНИЕ СВОЕГО КОМПОНЕНТА
// ============================================================

class SmartFilter extends \Lib\Component\ComponentBuilder
{
    protected $name = 'bitrix:catalog.smart.filter';
    protected $template = 'custom';

    protected $defaultParams = [
        "CACHE_GROUPS" => "Y",
        "CACHE_TIME" => "36000000",
        "CACHE_TYPE" => "A",
        "FILTER_NAME" => "arrFilter",
        "FILTER_VIEW_MODE" => "vertical",
        "IBLOCK_ID" => "2",
        "IBLOCK_TYPE" => "catalog",
        "SECTION_ID" => '',
        "SECTION_CODE" => '',
    ];

    // Разрешаем параметры из URL
    protected $allowRequestParams = ['sectionId', 'sectionCode'];

    public function __construct($params = [])
    {
        $this->params = $params;
    }

    // Фабрика для каталога
    public static function forCatalogList()
    {
        return (new static())->setParamsFromRequest();
    }

    // Готовый JSON endpoint
    public static function forCatalogListJsonResponse()
    {
        return self::forCatalogList()
            ->lowercaseKeys()
            ->getDataKeysResponse([
                'ITEMS',
                'FILTER_URL'
            ]);
    }

    // Маппинг параметров
    public function sectionId($value)
    {
        $this->params['SECTION_ID'] = $value;
    }

    public function sectionCode($value)
    {
        $this->params['SECTION_CODE'] = $value;
    }
}


// ============================================================
// ВЫХОД ИЗ namespace (важно!)
// ============================================================

// ============================================================
// 2. ПРОСТОЕ ИСПОЛЬЗОВАНИЕ
// ============================================================

SmartFilter::forCatalogList()->render();


// ============================================================
// 3. ПАРАМЕТРЫ ВРУЧНУЮ
// ============================================================

(new SmartFilter([
    'SECTION_ID' => 10
]))->render();


// ============================================================
// 4. ПАРАМЕТРЫ ИЗ URL
// ============================================================

SmartFilter::forCatalogList()->render();


// ============================================================
// 5. СМЕНА ШАБЛОНА
// ============================================================

SmartFilter::forCatalogList()
    ->setTemplate('ajax')
    ->render();


// ============================================================
// 6. ПОЛУЧИТЬ HTML КАК СТРОКУ
// ============================================================

$html = SmartFilter::forCatalogList()->render(true);

// ============================================================
// 7. ПОЛУЧЕНИЕ ДАННЫХ (без HTML)
// ============================================================

$data = SmartFilter::forCatalogList()
    ->getDataKeys(['ITEMS']);


// ============================================================
// 8. lowercaseKeys (удобно для JS)
// ============================================================

$data = SmartFilter::forCatalogList()
    ->lowercaseKeys()
    ->getDataKeys(['ITEMS']);

// ============================================================
// 9. JSON RESPONSE (без send)
// ============================================================


$response = SmartFilter::forCatalogList()
    ->getDataKeysResponse(['ITEMS']);

// ============================================================
// 10. JSON API (send)
// ============================================================

// 👉 использовать как /ajax/filter.php
/*
SmartFilter::forCatalogList()
    ->sendDataResponse(['ITEMS']);
*/


// ============================================================
// 11. ГОТОВЫЙ API МЕТОД
// ============================================================

/*
SmartFilter::forCatalogListJsonResponse()->send();
*/


// ============================================================
// 12. РУЧНОЕ УПРАВЛЕНИЕ ПАРАМЕТРАМИ
// ============================================================

$filter = SmartFilter::forCatalogList();

$filter->sectionId(15);
$filter->sectionCode('phones');

$filter->render();


// ============================================================
// 13. RESPONSE ОБЪЕКТ (для контроллеров)
// ============================================================

/*
$response = SmartFilter::forCatalogList()
    ->getResponse(['ITEMS']);

$response->send();
*/

