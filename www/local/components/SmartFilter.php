<?php
namespace Components;

class SmartFilter extends \Lib\Component\ComponentBuilder
{
    protected $name = 'bitrix:catalog.smart.filter';

    protected $defaultParams = [
        "CACHE_GROUPS" => "Y",
        "CACHE_TIME" => "36000000",
        "CACHE_TYPE" => "A",
        "CONVERT_CURRENCY" => "N",
        "DISPLAY_ELEMENT_COUNT" => "Y",
        "FILTER_NAME" => "arrFilter",
        "FILTER_VIEW_MODE" => "vertical",
        "HIDE_NOT_AVAILABLE" => "N",
        "IBLOCK_ID" => "2",
        "IBLOCK_TYPE" => "catalog",
        "PAGER_PARAMS_NAME" => "arrPager",
        "POPUP_POSITION" => "left",
        "PREFILTER_NAME" => "smartPreFilter",
        "PRICE_CODE" => array("BASE"),
        "SAVE_IN_SESSION" => "N",
        "SECTION_CODE" => "",
        "SECTION_DESCRIPTION" => "-",
        "SECTION_ID" => '',
        "SECTION_TITLE" => "-",
        "SEF_MODE" => "N",
        "TEMPLATE_THEME" => "blue",
        "XML_EXPORT" => "N"
    ];

    private $dataKeys = [
        'ITEMS',
        'PRICES',
        'FILTER_URL'
    ];

    protected $allowRequestParams = ['sectionId', 'sectionCode'];

    public function __construct($params = [])
    {
        $this->params = $params;
    }

    public static function forCatalogList()
    {
        $self = new static();
        $self->setParamsFromRequest();
        return $self;
    }

    public function getJsonResponse()
    {
        return $this->lowercaseKeys()->getDataKeysResponse($this->dataKeys);
    }


    public function renderWebComponent()
    {
        $data = $this->lowercaseKeys()->getDataKeys($this->dataKeys);
        $json = json_encode($data);
        echo "<smart-filter data-result='$json'></smart-filter>";
    }

    public function sectionId($value)
    {
        $this->params['SECTION_ID'] = $value;
    }

    public function sectionCode($value)
    {
        $this->params['SECTION_CODE'] = $value;
    }
}