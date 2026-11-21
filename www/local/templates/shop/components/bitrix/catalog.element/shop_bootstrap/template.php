<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */

$product = \App\Catalog\ProductCardData::fromSectionItem($arResult);
$offers = $product['offers'];
$hasOffers = !empty($offers);

$requestedOfferId = (int) ($_GET['offer'] ?? 0);
$selectedOfferId = $requestedOfferId > 0
    ? $requestedOfferId
    : (int) ($arResult['OFFERS'][$arResult['OFFERS_SELECTED'] ?? 0]['ID'] ?? 0);
$selectedOffer = null;

foreach ($offers as $offer) {
    if ($offer['id'] === $selectedOfferId) {
        $selectedOffer = $offer;
        break;
    }
}

if ($selectedOffer === null) {
    $selectedOffer = $offers[0] ?? null;
    $selectedOfferId = (int) ($selectedOffer['id'] ?? $arResult['ID']);
}

$price = $selectedOffer['price'] ?? $product['price'];
$image = $selectedOffer['image'] ?? '';

if ($image === '') {
    $image = $product['image'];
}

$available = $selectedOffer['available'] ?? $product['available'];
$name = $product['name'];
$article = $arResult['DISPLAY_PROPERTIES']['ARTNUMBER']['DISPLAY_VALUE'] ?? '';
$offerSelectId = 'offer-select-' . (int) $arResult['ID'];
?>
<div class="product-detail" data-product-detail>
    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <?php if ($image): ?>
                <img
                    data-product-image
                    src="<?= htmlspecialcharsbx($image) ?>"
                    alt="<?= htmlspecialcharsbx($name) ?>"
                    class="product-detail__img"
                >
            <?php else: ?>
                <div class="product-detail__img"></div>
            <?php endif; ?>
        </div>

        <div class="col-12 col-lg-6">
            <h1 class="h3 mb-2"><?= htmlspecialcharsbx($name) ?></h1>

            <?php if ($article): ?>
                <div class="small text-muted mb-3">Артикул: <?= htmlspecialcharsbx((string) $article) ?></div>
            <?php endif; ?>

            <div class="fs-3 fw-semibold mb-4" data-product-price>
                <?= $price !== '' ? $price : 'Цена по запросу' ?>
            </div>

            <?php if ($hasOffers): ?>
                <label class="form-label small text-muted" for="<?= $offerSelectId ?>">Вариант</label>
                <select id="<?= $offerSelectId ?>" class="form-select mb-4">
                    <?php foreach ($offers as $offer): ?>
                        <?php $offerImage = $offer['image'] ?: $product['image']; ?>
                        <option
                            value="<?= $offer['id'] ?>"
                            data-price="<?= htmlspecialcharsbx($offer['price']) ?>"
                            data-image="<?= htmlspecialcharsbx($offerImage) ?>"
                            data-available="<?= $offer['available'] ? 'true' : 'false' ?>"
                            <?= $offer['id'] === $selectedOfferId ? 'selected' : '' ?>
                        >
                            <?= htmlspecialcharsbx($offer['label']) ?><?= $offer['available'] ? '' : ' — нет в наличии' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>

            <product-buy
                data-product-id="<?= (int) $arResult['ID'] ?>"
                data-offer-select="<?= $hasOffers ? $offerSelectId : '' ?>"
                data-available="<?= $available ? 'true' : 'false' ?>"
            >
                <button type="button" class="btn btn-primary w-100">Купить</button>
            </product-buy>

            <?php if (!empty($arResult['DISPLAY_PROPERTIES'])): ?>
                <dl class="row small mt-4 mb-0">
                    <?php foreach ($arResult['DISPLAY_PROPERTIES'] as $property): ?>
                        <?php if (($property['CODE'] ?? '') === 'ARTNUMBER') { continue; } ?>
                        <?php
                        $propertyValue = $property['DISPLAY_VALUE'] ?? '';
                        if (is_array($propertyValue)) {
                            $propertyValue = implode(', ', $propertyValue);
                        }
                        ?>
                        <dt class="col-5 text-muted fw-normal"><?= htmlspecialcharsbx($property['NAME']) ?></dt>
                        <dd class="col-7 mb-2"><?= $propertyValue ?></dd>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($arResult['DETAIL_TEXT'])): ?>
        <div class="product-detail__text mt-4">
            <?= $arResult['DETAIL_TEXT'] ?>
        </div>
    <?php endif; ?>
</div>