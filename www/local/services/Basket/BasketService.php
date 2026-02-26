<?php

namespace Services\Basket;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Sale;
use Bitrix\Catalog;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Iblock\ElementTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Sale\DiscountCouponsManager;

class BasketService
{
    private Sale\Basket $basket;

    public function __construct()
    {
        Loader::includeModule('sale');
        Loader::includeModule('catalog');
        Loader::includeModule('iblock');

        $this->basket = Sale\Basket::loadItemsForFUser(
            Sale\Fuser::getId(),
            SITE_ID
        );
    }

    /*=====================================
    =            PUBLIC API               =
    =====================================*/

    public function add(int $productId, float $quantity = 1): void
    {
        $items = $this->basket->getExistsItems('catalog', $productId);

        if (!empty($items)) {
            foreach ($items as $item) {
                $item->setField('QUANTITY', $item->getQuantity() + $quantity);
            }
        } else {
            $item = $this->basket->createItem('catalog', $productId);

            $item->setFields([
                'QUANTITY' => $quantity,
                'CURRENCY' => CurrencyManager::getBaseCurrency(),
                'LID' => SITE_ID,
                'PRODUCT_PROVIDER_CLASS' => Catalog\Product\CatalogProvider::class,
            ]);
        }

        $this->save();
    }

    public function remove(int $productId): void
    {
        $items = $this->basket->getExistsItems('catalog', $productId);

        foreach ($items as $item) {
            $item->delete();
        }

        $this->save();
    }

    public function clear(): void
    {
        foreach ($this->basket as $item) {
            $item->delete();
        }

        $this->save();
    }

    public function applyCoupon(string $coupon): bool
    {
        DiscountCouponsManager::init();

        if (!DiscountCouponsManager::add($coupon)) {
            return false;
        }

        $this->refresh();
        $this->save();

        return true;
    }

    public function getData(): array
    {
        $this->refresh();

        $items = iterator_to_array($this->basket);

        if (!$items) {
            return [
                'items' => [],
                'summary' => $this->getSummary()
            ];
        }

        $productIds = array_map(
            fn($item) => $item->getProductId(),
            $items
        );

        $elements = $this->loadElements($productIds);
        $skuMap = $this->loadSkuMap($productIds);

        $resultItems = [];

        foreach ($items as $item) {

            $productId = $item->getProductId();
            $element = $elements[$productId] ?? null;

            $resultItems[] = array_merge([
                'id' => $item->getId(),
                'product_id' => $productId,
                'name' => $element['NAME'] ?? '',
                'quantity' => $item->getQuantity(),
                'currency' => $item->getCurrency(),
                'is_sku' => $skuMap[$productId] ?? false,
                'detail_url' => $element['DETAIL_PAGE_URL'] ?? '',
                'image' => $this->getImage($element),
            ], $this->buildPriceData($item));
        }

        return [
            'items' => $resultItems,
            'summary' => $this->getSummary(),
        ];
    }

    /*=====================================
    =           PRICE LOGIC               =
    =====================================*/

    private function buildPriceData(Sale\BasketItem $item): array
    {
        $basePrice = (float) $item->getBasePrice();
        $finalPrice = (float) $item->getPrice();

        $discountValue = $basePrice - $finalPrice;

        $discountPercent = $basePrice > 0
            ? round(($discountValue / $basePrice) * 100, 2)
            : 0;

        return [
            'price' => $basePrice,
            'old_price' => $discountValue > 0 ? $basePrice : null,
            'final_price' => $finalPrice,
            'discount_value' => $discountValue,
            'discount_percent' => $discountPercent,
        ];
    }

    private function getSummary(): array
    {
        $basePrice = (float) $this->basket->getBasePrice();
        $finalPrice = (float) $this->basket->getPrice();

        $discountValue = $basePrice - $finalPrice;

        $discountPercent = $basePrice > 0
            ? round(($discountValue / $basePrice) * 100, 2)
            : 0;

        return [
            'count' => $this->getTotalQuantity(),
            'base_price' => $basePrice,
            'final_price' => $finalPrice,
            'discount_value' => $discountValue,
            'discount_percent' => $discountPercent,
            'currency' => CurrencyManager::getBaseCurrency(),
        ];
    }

    /*=====================================
    =           INTERNAL HELPERS          =
    =====================================*/

    private function refresh(): void
    {
        $result = $this->basket->refresh(
            Sale\Basket\RefreshFactory::create(
                Sale\Basket\RefreshFactory::TYPE_FULL
            )
        );

        if (!$result->isSuccess()) {
            throw new SystemException(
                implode(', ', $result->getErrorMessages())
            );
        }
    }

    private function save(): void
    {
        $result = $this->basket->save();

        if (!$result->isSuccess()) {
            throw new SystemException(
                implode(', ', $result->getErrorMessages())
            );
        }
    }

    private function getTotalQuantity(): float
    {
        $quantity = 0;

        foreach ($this->basket as $item) {
            $quantity += $item->getQuantity();
        }

        return $quantity;
    }

    private function loadElements(array $ids): array
    {
        if (!$ids)
            return [];

        $rows = ElementTable::getList([
            'filter' => ['=ID' => $ids],
            'select' => [
                'ID',
                'NAME',
                'IBLOCK_ID',
                'PREVIEW_PICTURE',
                'IBLOCK.DETAIL_PAGE_URL'
            ]
        ])->fetchAll();

        foreach ($rows as &$row) {
            $row['DETAIL_PAGE_URL'] = \CIBlock::ReplaceDetailUrl(
                $row['IBLOCK_ELEMENT_IBLOCK_DETAIL_PAGE_URL'],
                $row,
                false,
                'E'
            );
        }

        return array_column($rows, null, 'ID');
    }

    private function loadSkuMap(array $ids): array
    {
        if (!$ids)
            return [];

        $rows = ProductTable::getList([
            'filter' => ['=ID' => $ids],
            'select' => ['ID', 'TYPE']
        ])->fetchAll();

        $map = [];

        foreach ($rows as $row) {
            $map[$row['ID']] = ($row['TYPE'] == ProductTable::TYPE_SKU);
        }

        return $map;
    }

    private function getImage(?array $element): ?string
    {
        if (!$element || empty($element['PREVIEW_PICTURE'])) {
            return null;
        }

        return \CFile::GetPath($element['PREVIEW_PICTURE']);
    }
}