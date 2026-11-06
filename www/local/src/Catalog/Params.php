<?php

namespace App\Catalog;

/**
 * Единые параметры компонентов каталога.
 *
 * Используются и страницей каталога, и ajax-эндпоинтами — чтобы разметка
 * из /api/catalog/list совпадала с серверной.
 */
final class Params
{
    public const IBLOCK_TYPE = 'catalog';
    public const IBLOCK_ID = 2;

    /** Код типа цены (b_catalog_group.NAME). */
    public const PRICE_CODE = ['BASE'];

    /** Имя глобальной переменной, в которую смарт-фильтр складывает фильтр. */
    public const FILTER_NAME = 'arrFilter';

    public const PAGE_SIZE = 12;

    /** Страница, на которой живёт список (нужна пагинации в ajax-ответе). */
    public const LIST_PAGE_URL = '/catalog/';

    /** Куда компоненту фильтра отправлять запросы за счётчиками. */
    public const FILTER_URL = '/api/catalog/filter';

    /** Куда компоненту списка отправлять запросы за фрагментом. */
    public const LIST_URL = '/api/catalog/list';

    /**
     * Параметры bitrix:catalog.smart.filter.
     */
    public static function filter(): array
    {
        return [
            'IBLOCK_TYPE' => self::IBLOCK_TYPE,
            'IBLOCK_ID' => self::IBLOCK_ID,
            'SECTION_ID' => '',
            'FILTER_NAME' => self::FILTER_NAME,
            'COUNTS_URL' => self::FILTER_URL,
            'PRICE_CODE' => self::PRICE_CODE,
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => '36000000',
            'CACHE_GROUPS' => 'Y',
            'SAVE_IN_SESSION' => 'N',
            'DISPLAY_ELEMENT_COUNT' => 'Y',
            'XML_EXPORT' => 'N',
            'CONVERT_CURRENCY' => 'N',
            'HIDE_NOT_AVAILABLE' => 'N',
        ];
    }

    /**
     * Параметры bitrix:catalog.section.
     */
    public static function section(?string $sortCode = null, ?string $basePage = null): array
    {
        $sort = Sort::resolve($sortCode);

        $params = [
            'IBLOCK_TYPE' => self::IBLOCK_TYPE,
            'IBLOCK_ID' => self::IBLOCK_ID,
            'SECTION_ID' => '',
            'SECTION_CODE' => '',
            'SHOW_ALL_WO_SECTION' => 'Y',
            'INCLUDE_SUBSECTIONS' => 'Y',
            'FILTER_NAME' => self::FILTER_NAME,
            'PRICE_CODE' => self::PRICE_CODE,
            'PRICE_VAT_INCLUDE' => 'Y',
            'CONVERT_CURRENCY' => 'N',
            'CACHE_TYPE' => 'A',
            'CACHE_TIME' => '3600',
            'CACHE_GROUPS' => 'Y',
            'LIST_URL' => self::LIST_URL,
            'PAGE_ELEMENT_COUNT' => (string)self::PAGE_SIZE,
            'LINE_ELEMENT_COUNT' => '3',
            'ELEMENT_SORT_FIELD' => $sort['field'],
            'ELEMENT_SORT_ORDER' => $sort['order'],
            'ELEMENT_SORT_FIELD2' => $sort['field2'],
            'ELEMENT_SORT_ORDER2' => $sort['order2'],
            'PROPERTY_CODE' => [],
            'OFFERS_LIMIT' => '5',
            'DETAIL_URL' => '/catalog/#SECTION_CODE#/#ELEMENT_CODE#/',
            'SECTION_URL' => '/catalog/#SECTION_CODE#/',
            'SHOW_OLD_PRICE' => 'Y',
            'SHOW_DISCOUNT_PERCENT' => 'Y',
            'USE_PRICE_COUNT' => 'N',
            'PRODUCT_SUBSCRIPTION' => 'N',
            'DISPLAY_COMPARE' => 'N',
            'ADD_PICT_PROP' => '-',
            'LABEL_PROP' => '-',
            'ADD_SECTIONS_CHAIN' => 'N',
            'SET_TITLE' => 'N',
            'SET_BROWSER_TITLE' => 'N',
            'SET_META_KEYWORDS' => 'N',
            'SET_META_DESCRIPTION' => 'N',
            'SET_LAST_MODIFIED' => 'N',
            'META_KEYWORDS' => '-',
            'META_DESCRIPTION' => '-',
            'BROWSER_TITLE' => '-',
            'COMPATIBLE_MODE' => 'N',
            'DISABLE_INIT_JS_IN_COMPONENT' => 'Y',
            'PAGER_TEMPLATE' => 'bootstrap_v4',
            'PAGER_TITLE' => 'Товары',
            'PAGER_SHOW_ALWAYS' => 'N',
            'DISPLAY_TOP_PAGER' => 'N',
            'DISPLAY_BOTTOM_PAGER' => 'Y',
        ];

        // Ссылки пагинации должны вести на настоящую страницу, а не на api-эндпоинт
        if ($basePage !== null) {
            $params['CURRENT_BASE_PAGE'] = $basePage;
        }

        return $params;
    }
}
