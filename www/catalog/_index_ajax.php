<?php
/**
 * Каталог: фильтр в сайдбаре, сортировка и список товаров.
 *
 * AJAX идёт на этот же URL (POST + ajax_action) и обрабатывается
 * здесь же до подключения шаблона сайта.
 */

use App\Helper\CatalogAjax;
use App\Helper\CatalogAjaxAction;
use Bitrix\Main\Loader;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['ajax_action'])) {

    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

    $resetFilter = static function (): void {
        $GLOBALS['arrFilter'] = [];
    };

    (new CatalogAjax([
        'list' => new CatalogAjaxAction(
            render: '/local/include/components/catalog_list.php',
            prepare: [
                '/local/include/components/catalog_sort.php',
                '/local/include/components/catalog_smart_filter.php',
            ],
            init: $resetFilter,
            response: CatalogAjax::RESPONSE_HTML,
        ),
        'filter' => new CatalogAjaxAction(
            render: '/local/include/components/catalog_smart_filter.php',
            init: $resetFilter,
            returnResult: true,
            response: CatalogAjax::RESPONSE_RESULT,
            resultKeys: ['COUNTS', 'TOTAL'],
        ),
    ]))->handle();
}
