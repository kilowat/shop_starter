<?php


require($_SERVER['DOCUMENT_ROOT'] . '/catalog/_index_ajax.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');




addAssetCss([
    'js/nouislider/nouislider.min.css',
]);

addAssetJs([
    'js/nouislider/nouislider.min.js',
    'js/signa/dist/signa.min.js',
    'js/catalog/catalog.js',
    'js/catalog/catalog-sort.js',
    'js/catalog/smart-filter.js',
    'js/catalog/catalog-selected.js',
]);

// Смарт-фильтр заполнит эту переменную, список прочитает её по FILTER_NAME
global $arrFilter;
$arrFilter = [];
?>
<div class="container">
    <div class="row g-4">

        <aside class="col-12 col-lg-3">
            <div class="catalog-sidebar">
                <?php require $_SERVER['DOCUMENT_ROOT'] . '/local/include/components/catalog_smart_filter.php'; ?>
            </div>
        </aside>

        <div class="col-12 col-lg-9">
            <?php require $_SERVER['DOCUMENT_ROOT'] . '/local/include/components/catalog_sort.php'; ?>

            <catalog-selected style="height: 60px; display: block; margin-top: 10px;"></catalog-selected>

            <div id="catalog-list">
                <?php require $_SERVER['DOCUMENT_ROOT'] . '/local/include/components/catalog_list.php'; ?>
            </div>
        </div>

    </div>
</div>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>