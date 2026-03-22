<?php

namespace Components;

class SmartFilter extends \Lib\ComponentBuilder
{
    protected $name = 'bitrix:catalog.smart.filter';

    protected $template = 'custom';

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

    protected $dataKeys = [
        'ITEMS',
        'PRICES',
        'FILTER_URL',
    ];

    protected $allowRequestParams = ['sectionId', 'sectionCode'];

    public static function catalogList()
    {
        $self = new static();
        $self->setParamsFromRequest();
        return $self;
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
