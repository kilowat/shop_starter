<?php
/**
 * Каталог: фильтр в сайдбаре, сортировка и список товаров.
 *
 * Список с пагинацией подгружается через GET /api/catalog/list (catalog.js),
 * живые счётчики фильтра — через GET /api/catalog/filter (smart-filter.js).
 */

use App\Catalog\Params;
use App\Catalog\Sort;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

/** @var CMain $APPLICATION */

Loader::includeModule('iblock');
Loader::includeModule('catalog');

$APPLICATION->SetTitle('Каталог');

$request = Context::getCurrent()->getRequest();
$sortCode = Sort::resolve($request->getQuery('sort'))['code'];
$sectionParams = Params::section($sortCode, $request->getRequestUri());

// Ассеты подключаем на странице: шаблоны фильтра и списка используются
// ещё и в ajax-эндпоинтах, где подключать CSS/JS не нужно
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
                <?php $APPLICATION->IncludeComponent(
                    'bitrix:catalog.smart.filter',
                    'signa',
                    Params::filter(),
                    false,
                    ['HIDE_ICONS' => 'Y']
                ); ?>
            </div>
        </aside>

        <div class="col-12 col-lg-9">
            <catalog-sort data-sorts="<?= htmlspecialcharsbx(Json::encode(Sort::options(), JSON_UNESCAPED_UNICODE)) ?>"></catalog-sort>

            <div data-catalog-list data-list-url="<?= htmlspecialcharsbx($sectionParams['LIST_URL']) ?>">
                <?php $APPLICATION->IncludeComponent(
                    'bitrix:catalog.section',
                    'shop_bootstrap',
                    $sectionParams,
                    false,
                    ['HIDE_ICONS' => 'Y']
                ); ?>
            </div>
        </div>

    </div>
</div>
<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>
