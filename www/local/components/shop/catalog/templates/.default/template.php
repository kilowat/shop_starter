<?php
/**
 * Шаблон каталога: сайдбар с фильтром, сортировка и список товаров.
 *
 * Вложенные компоненты подключаются методами класса — так шаблон остаётся
 * разметкой, а настройки живут в параметрах shop:catalog.
 *
 * @var ShopCatalogComponent $component
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

addAssetCss([
    'js/nouislider/nouislider.min.css',
]);

addAssetJs([
    'js/nouislider/nouislider.min.js',
    'js/signa/dist/signa.min.js',
    'js/catalog/catalog.js',
    'js/catalog/filter.js',
    'js/catalog/sort.js',
]);
?>
<div class="container">
    <div class="row g-4">

        <aside class="col-12 col-lg-3">
            <div class="catalog-sidebar">
                <?php $component->renderFilter(); ?>
            </div>
        </aside>

        <div class="col-12 col-lg-9" id="catalog-results">
            <?php $component->renderSort(); ?>
            <br>
            <div id="catalog-list" data-pagination-list>
                <?php $component->renderList(); ?>
            </div>
        </div>

    </div>
</div>