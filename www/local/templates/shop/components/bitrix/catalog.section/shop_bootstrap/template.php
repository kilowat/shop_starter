<?
/**
 * Список товаров (bitrix:catalog.section), Bootstrap 5.
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<div class="catalog">
    <? if (empty($arResult['ITEMS'] ?? $_REQUEST['set_filter'] == 'y')): ?>
        <div class="alert alert-light border mb-0">По выбранным условиям товары не найдены.</div>
    <? else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3" data-pagination-items>
            <? foreach ($arResult['ITEMS'] as $item): ?>
                <?
                $name = htmlspecialcharsbx($item['NAME']);
                $hasOffers = !empty($item['OFFERS']);

                // Стандартное получение цены для простого товара
                $price = $item['ITEM_PRICES'][$item['ITEM_PRICE_SELECTED']]['PRINT_PRICE'] ?? '';

                // Если есть торговые предложения — берём минимальную цену из OFFERS
                if ($hasOffers) {
                    $minOfferPrice = null;
                    foreach ($item['OFFERS'] as $offer) {
                        if (empty($offer['ITEM_PRICES'][$offer['ITEM_PRICE_SELECTED']]['PRINT_PRICE'])) {
                            continue;
                        }
                        $offerPriceValue = $offer['ITEM_PRICES'][$offer['ITEM_PRICE_SELECTED']]['PRICE']
                            ?? $offer['ITEM_PRICES'][$offer['ITEM_PRICE_SELECTED']]['VALUE']
                            ?? 0;
                        if ($minOfferPrice === null || $offerPriceValue < $minOfferPrice) {
                            $minOfferPrice = $offerPriceValue;
                            $price = $offer['ITEM_PRICES'][$offer['ITEM_PRICE_SELECTED']]['PRINT_PRICE'];
                        }
                    }
                }
                ?>
                <div class="col" data-pagination-item>
                    <article class="card h-100 product-card">
                        <? if ($item['PREVIEW_PICTURE']): ?>
                            <?= imgTag($item['PREVIEW_PICTURE']['ID'], attrs: [
                                'class' => 'card-img-top product-card__img',
                                'alt' => $name,
                            ]) ?>
                        <? else: ?>
                            <div class="product-card__img bg-light"></div>
                        <? endif; ?>

                        <div class="card-body d-flex flex-column">
                            <h3 class="h6 card-title">
                                <a href="<?= $item['DETAIL_PAGE_URL'] ?>"
                                    class="stretched-link text-body text-decoration-none"><?= $name ?></a>
                            </h3>

                            <div class="fs-5 fw-semibold mt-auto">
                                <? if ($price): ?>
                                    <?= $hasOffers ? 'от ' : '' ?>            <?= $price ?>
                                <? else: ?>
                                    Цена по запросу
                                <? endif; ?>
                            </div>
                        </div>

                        <div class="card-footer bg-transparent border-0 pt-0">
                            <button type="button" class="btn btn-primary w-100 buy-button position-relative z-2"
                                data-product-id="<?= (int) $item['ID'] ?>">Купить</button>
                        </div>
                    </article>
                </div>
            <? endforeach; ?>
        </div>

        <? if (!empty($arResult['NAV_STRING'])): ?>
            <div class="catalog-pagination mt-4"><?= $arResult['NAV_STRING'] ?></div>
        <? endif; ?>
    <? endif; ?>
</div>