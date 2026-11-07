<?php
/**
 * AJAX каталога.
 *
 * Здесь только конфигурация действий: какие include-файлы выполнить
 * и что вернуть. Обработка — в App\Helper\CatalogAjax.
 */

use App\Helper\CatalogAjax;
use App\Helper\CatalogAjaxAction;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$resetFilter = static function (): void {
    $GLOBALS['arrFilter'] = [];
};

(new CatalogAjax(
    [
        'list' => new CatalogAjaxAction(
            render: '/local/include/components/catalog_list.php',
            prepare: [
                '/local/include/components/catalog_sort.php',
                '/local/include/components/smart_filter.php',
            ],
            init: $resetFilter,
            response: CatalogAjax::RESPONSE_HTML,
        ),
        'filter' => new CatalogAjaxAction(
            render: '/local/include/components/smart_filter.php',
            init: $resetFilter,
            returnResult: true,
            response: CatalogAjax::RESPONSE_RESULT,
            resultKeys: ['COUNTS', 'TOTAL'],
        ),
    ],
    actionField: 'catalog_action'
))->handle();
