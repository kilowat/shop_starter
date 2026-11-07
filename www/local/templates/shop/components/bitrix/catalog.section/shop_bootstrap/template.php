<?php
/**
 * Список товаров каталога (bitrix:catalog.section) на Bootstrap 5.
 *
 * Своя вёрстка карточки: картинка, название со ссылкой на детальную,
 * цена и кнопка «Купить» (пока заглушка — корзина не подключена).
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/**
 * Картинка товара: PREVIEW_PICTURE, при отсутствии — DETAIL_PICTURE.
 */
$getPictureSrc = static function (array $item): string {
    foreach (['PREVIEW_PICTURE', 'DETAIL_PICTURE'] as $field) {
        $picture = $item[$field] ?? null;

        if (empty($picture)) {
            continue;
        }

        if (is_array($picture)) {
            $fileId = (int)($picture['ID'] ?? 0);

            if ($fileId > 0) {
                $resized = CFile::ResizeImageGet(
                    $fileId,
                    ['width' => 600, 'height' => 600],
                    BX_RESIZE_IMAGE_PROPORTIONAL,
                    true
                );

                if (!empty($resized['src'])) {
                    return $resized['src'];
                }
            }

            if (!empty($picture['SRC'])) {
                return $picture['SRC'];
            }

            continue;
        }

        if (is_numeric($picture)) {
            $src = (string)CFile::GetPath($picture);

            if ($src !== '') {
                return $src;
            }
        }
    }

    return '';
};

/**
 * Цена: ['html' => строка с валютой, 'from' => показывать «от»].
 */
$getPrice = static function (array $item): array {
    $selectedIndex = $item['ITEM_PRICE_SELECTED'] ?? null;

    if ($selectedIndex !== null && !empty($item['ITEM_PRICES'][$selectedIndex]['PRINT_PRICE'])) {
        return [
            'html' => $item['ITEM_PRICES'][$selectedIndex]['PRINT_PRICE'],
            'from' => false,
        ];
    }

    if (!empty($item['ITEM_START_PRICE']['PRINT_PRICE'])) {
        return [
            'html' => $item['ITEM_START_PRICE']['PRINT_PRICE'],
            'from' => true,
        ];
    }

    if (!empty($item['MIN_PRICE']['PRINT_DISCOUNT_VALUE'])) {
        return [
            'html' => $item['MIN_PRICE']['PRINT_DISCOUNT_VALUE'],
            'from' => true,
        ];
    }

    $minValue = null;
    $minHtml = '';

    foreach ($item['OFFERS'] ?? [] as $offer) {
        $offerIndex = $offer['ITEM_PRICE_SELECTED'] ?? null;
        $offerPrice = $offerIndex !== null ? ($offer['ITEM_PRICES'][$offerIndex] ?? null) : null;

        if (empty($offerPrice['PRICE']) || empty($offer['CAN_BUY'])) {
            continue;
        }

        if ($minValue === null || $offerPrice['PRICE'] < $minValue) {
            $minValue = $offerPrice['PRICE'];
            $minHtml = $offerPrice['PRINT_PRICE'] ?? '';
        }
    }

    if ($minHtml !== '') {
        return ['html' => $minHtml, 'from' => true];
    }

    return ['html' => '', 'from' => false];
};
?>
<div class="catalog-fragment">
<?php if (empty($arResult['ITEMS'])): ?>
    <div class="alert alert-light border mb-0" role="alert">
        По выбранным условиям товары не найдены.
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
        <?php foreach ($arResult['ITEMS'] as $arItem):
            $itemName = (string)$arItem['NAME'];
            $detailUrl = (string)($arItem['DETAIL_PAGE_URL'] ?? '');
            $pictureSrc = $getPictureSrc($arItem);
            $price = $getPrice($arItem);
            ?>
            <div class="col">
                <article class="card h-100 product-card">
                    <a href="<?= $detailUrl ?>" class="text-decoration-none d-block">
                        <?php if ($pictureSrc !== ''): ?>
                            <img
                                src="<?= $pictureSrc ?>"
                                class="card-img-top product-card__img"
                                alt="<?= htmlspecialcharsbx($itemName) ?>"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="product-card__img d-flex align-items-center justify-content-center text-muted small">
                                Нет фото
                            </div>
                        <?php endif; ?>
                    </a>

                    <div class="card-body d-flex flex-column">
                        <h3 class="h6 card-title mb-2">
                            <a href="<?= $detailUrl ?>" class="text-decoration-none text-body">
                                <?= htmlspecialcharsbx($itemName) ?>
                            </a>
                        </h3>

                        <div class="mt-auto pt-2">
                            <div class="fs-5 fw-semibold mb-3">
                                <?php if ($price['html'] === ''): ?>
                                    Цена по запросу
                                <?php else: ?>
                                    <?= $price['from'] ? 'от ' : '' ?><?= $price['html'] ?>
                                <?php endif; ?>
                            </div>

                            <button
                                type="button"
                                class="btn btn-primary w-100 buy-button"
                                data-product-id="<?= (int)$arItem['ID'] ?>"
                            >Купить</button>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($arResult['NAV_STRING'])): ?>
        <nav class="mt-4" aria-label="Навигация по страницам каталога">
            <?= $arResult['NAV_STRING'] ?>
        </nav>
    <?php endif; ?>
<?php endif; ?>
</div>
