<?php
/**
 * Каталог: фильтр в сайдбаре, сортировка и список товаров.
 *
 * AJAX идёт на этот же URL (POST + catalog_action) и обрабатывается
 * в catalog/_ajax.php до подключения шаблона сайта.
 */

use Bitrix\Main\Loader;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['catalog_action'])) {
    define('NO_KEEP_STATISTIC', true);
    define('STOP_STATISTICS', true);
    define('NO_AGENT_STATISTIC', true);
    define('NO_AGENT_CHECK', true);

    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
    require __DIR__ . '/_ajax.php';

    exit;
}

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

/** @var CMain $APPLICATION */

Loader::includeModule('iblock');
Loader::includeModule('catalog');

$APPLICATION->SetTitle('Каталог');

addAssetCss([
    'js/libs/nouislider/nouislider.min.css',
]);

addAssetJs([
    'js/libs/nouislider/nouislider.min.js',
    'js/signa/dist/signa.min.js',
    'js/app/catalog.js',
    'js/app/catalog-sort.js',
    'js/app/smart-filter.js',
]);

// Смарт-фильтр заполнит эту переменную, список прочитает её по FILTER_NAME
global $arrFilter;
$arrFilter = [];
?>
<div class="container">
    <div class="row g-4">

        <aside class="col-12 col-lg-3">
            <div class="catalog-sidebar">
                <?php require $_SERVER['DOCUMENT_ROOT'] . '/local/include/components/smart_filter.php'; ?>
            </div>
        </aside>

        <div class="col-12 col-lg-9">
            <?php require $_SERVER['DOCUMENT_ROOT'] . '/local/include/components/catalog_sort.php'; ?>

            <div id="catalog-list">
                <?php require $_SERVER['DOCUMENT_ROOT'] . '/local/include/components/catalog_list.php'; ?>
            </div>
        </div>

    </div>
</div>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>
