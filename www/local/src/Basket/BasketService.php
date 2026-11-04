<?php

namespace App\Basket;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Sale;
use Bitrix\Catalog;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Iblock\ElementTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Sale\DiscountCouponsManager;
use Bitrix\Sale\PriceMaths;
use CFile;
use Bitrix\Main\Config\Option;

class BasketService
{
    private Sale\Basket $basket;
    private array $discountData = [];
    private bool $discountsCalculated = false;
    private array $skuParentMap = [];  // Карта соответствия оффер -> родитель
    private array $elementUrlMap = []; // Карта URL элементов

    private string $productProviderClass;

    public function __construct($productProviderClass = Catalog\Product\CatalogProvider::class)
    {
        Loader::includeModule('sale');
        Loader::includeModule('catalog');
        Loader::includeModule('iblock');
        Loader::includeModule('currency');

        $this->productProviderClass = $productProviderClass;

        $this->basket = Sale\Basket::loadItemsForFUser(
            Sale\Fuser::getId(),
            SITE_ID
        );

        // Инициализируем менеджер купонов
        DiscountCouponsManager::init();
    }

    /*=====================================
    =            PUBLIC API               =
    =====================================*/

    /**
     * Добавить товар в корзину.
     *
     * - Если товар уже в корзине (с теми же свойствами) – количество увеличивается.
     * - Если $quantity не передан – используется шаг товара (по умолчанию 1).
     * - Количество приводится к кратному шагу и обрезается по остатку.
     *
     * @return array{id:int, quantity:float, deleted:bool}
     */
    public function add(string|int $productId, ?float $quantity = null, array $props = []): array
    {
        $productId = (int) $productId;

        if ($productId <= 0) {
            throw new SystemException('Некорректный ID товара');
        }

        $product = ProductTable::getById($productId)->fetch();

        if (!$product) {
            throw new SystemException('Товар не найден');
        }

        // Родительский товар с торговыми предложениями добавлять нельзя.
        if ((int) $product['TYPE'] === ProductTable::TYPE_SKU) {
            throw new SystemException(
                'Нельзя добавить товар напрямую. Выберите торговое предложение.'
            );
        }

        if ($product['AVAILABLE'] !== 'Y') {
            throw new SystemException('Товар недоступен для покупки');
        }

        $ratio = $this->getProductRatio($productId);
        $quantity ??= $ratio;

        if ($quantity <= 0) {
            throw new SystemException('Количество товара должно быть больше нуля');
        }

        // Ищем существующую позицию (с учётом свойств)
        $existing = $this->basket->getExistsItems('catalog', $productId, $props);
        $item = $existing ? reset($existing) : null;

        if ($item) {
            // Повторное добавление = инкремент
            $newQuantity = $this->normalizeQuantity($productId, $item->getQuantity() + $quantity);
            $this->changeQuantity($item, $newQuantity);
            $this->save();

            return $this->itemState($item);
        }

        $quantity = $this->normalizeQuantity($productId, $quantity);

        $item = $this->basket->createItem('catalog', $productId);

        $result = $item->setFields([
            'QUANTITY' => $quantity,
            'CURRENCY' => CurrencyManager::getBaseCurrency(),
            'LID' => SITE_ID,
            'PRODUCT_PROVIDER_CLASS' => $this->productProviderClass,
        ]);

        if (!$result->isSuccess()) {
            throw new SystemException(implode(', ', $result->getErrorMessages()));
        }

        if ($props) {
            $collection = $item->getPropertyCollection();

            foreach ($props as $prop) {
                $property = $collection->createItem();
                $property->setFields($prop);
            }
        }

        $this->save();

        return $this->itemState($item);
    }

    /**
     * Увеличить количество позиции на шаг (или на $step).
     *
     * @return array{id:int, quantity:float, deleted:bool}
     */
    public function increment(string|int $basketItemId, ?float $step = null): array
    {
        $item = $this->getItemOrFail($basketItemId);
        $productId = (int) $item->getProductId();

        $step ??= $this->getProductRatio($productId);

        if ($step <= 0) {
            throw new SystemException('Шаг должен быть больше нуля');
        }

        $newQuantity = $this->normalizeQuantity($productId, $item->getQuantity() + $step);

        $this->changeQuantity($item, $newQuantity);
        $this->save();

        return $this->itemState($item);
    }

    /**
     * Уменьшить количество позиции на шаг (или на $step).
     * Если количество становится <= 0 – позиция удаляется.
     *
     * @return array{id:int, quantity:float, deleted:bool}
     */
    public function decrement(string|int $basketItemId, ?float $step = null): array
    {
        $item = $this->getItemOrFail($basketItemId);
        $productId = (int) $item->getProductId();
        $id = (int) $item->getId();

        $step ??= $this->getProductRatio($productId);

        if ($step <= 0) {
            throw new SystemException('Шаг должен быть больше нуля');
        }

        $newQuantity = round($item->getQuantity() - $step, 4);

        if ($newQuantity <= 0) {
            $item->delete();
            $this->save();

            return ['id' => $id, 'quantity' => 0.0, 'deleted' => true];
        }

        $this->changeQuantity($item, $this->normalizeQuantity($productId, $newQuantity));
        $this->save();

        return $this->itemState($item);
    }

    /**
     * Установить абсолютное количество (идемпотентно – безопасно для частых кликов).
     * Значение приводится к допустимому шагу и обрезается по остатку.
     * В ответе возвращается фактическое количество.
     *
     * @return array{id:int, quantity:float, deleted:bool}
     */
    public function updateQuantity(string|int $basketItemId, float $quantity): array
    {
        $item = $this->getItemOrFail($basketItemId);
        $id = (int) $item->getId();

        if ($quantity <= 0) {
            $item->delete();
            $this->save();

            return ['id' => $id, 'quantity' => 0.0, 'deleted' => true];
        }

        $quantity = $this->normalizeQuantity((int) $item->getProductId(), $quantity);

        $this->changeQuantity($item, $quantity);
        $this->save();

        return $this->itemState($item);
    }

    public function remove(string|int $productId): void
    {
        $items = $this->basket->getExistsItems('catalog', $productId);

        foreach ($items as $item) {
            $item->delete();
        }

        $this->save();
    }

    public function removeById(string|int $basketItemId): void
    {
        $item = $this->basket->getItemById((int) $basketItemId);

        if ($item) {
            $item->delete();
            $this->save();
        }
    }

    public function clear(): void
    {
        foreach ($this->basket as $item) {
            $item->delete();
        }

        $this->save();
    }

    public function setDelay(string|int $basketItemId, bool $delay = true): void
    {
        $item = $this->basket->getItemById((int) $basketItemId);

        if ($item && in_array('DELAY', $this->getAvailableColumns())) {
            $item->setField('DELAY', $delay ? 'Y' : 'N');
            $this->save();
        }
    }

    /**
     * Применить купон
     */
    public function applyCoupon(string $coupon): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'coupon' => $coupon
        ];

        $coupon = trim($coupon);

        if (empty($coupon)) {
            DiscountCouponsManager::clear(true);
            $result['message'] = 'Купоны очищены';
            $result['success'] = true;
        } else {
            if (DiscountCouponsManager::add($coupon)) {
                $result['success'] = true;
                $result['message'] = 'Купон успешно применен';
            } else {
                $result['message'] = 'Не удалось применить купон';
            }
        }

        // Обновляем корзину после применения купона
        $this->refresh();
        $this->save();

        return $result;
    }

    /**
     * Удалить купон
     */
    public function removeCoupon(string $coupon): bool
    {
        $result = DiscountCouponsManager::delete($coupon);

        if ($result) {
            $this->refresh();
            $this->save();
        }

        return $result;
    }

    /**
     * Получить информацию о купонах
     */
    public function getCoupons(): array
    {
        $coupons = DiscountCouponsManager::get(true, [], true, true);
        $result = [];

        foreach ($coupons as $coupon) {
            $status = $this->getCouponStatus($coupon);

            $result[] = [
                'coupon' => $coupon['COUPON'],
                'status' => $status['code'],
                'status_text' => $status['text'],
                'description' => $coupon['DISCOUNT_NAME'] ?? '',
            ];
        }

        return $result;
    }

    public function getData(): array
    {
        // Сначала обновляем корзину: refresh() перезаписывает цены данными
        // провайдера, поэтому скидки нужно считать уже после него.
        $this->discountsCalculated = false;
        $this->refresh();
        $this->initializeDiscounts();

        $items = iterator_to_array($this->basket);

        if (!$items) {
            return [
                'items' => [],
                'summary' => $this->getSummary(),
                'coupons' => $this->getCoupons(),
            ];
        }

        $productIds = array_map(
            fn($item) => $item->getProductId(),
            $items
        );

        // Загружаем информацию о родителях для офферов
        $this->loadSkuParentMap($productIds);

        // Загружаем элементы (и офферы и родители)
        $allElementIds = array_unique(array_merge(
            $productIds,
            array_values($this->skuParentMap)
        ));

        $elements = $this->loadElements($allElementIds);
        $this->buildElementUrlMap($elements);

        $productTypes = $this->loadProductTypes($productIds);
        $ratioData = $this->getRatioData();

        $resultItems = [];

        foreach ($items as $item) {
            $productId = $item->getProductId();
            $element = $elements[$productId] ?? null;
            $basketCode = $item->getBasketCode();

            // Определяем parent_id (для офферов)
            $parentId = $this->skuParentMap[$productId] ?? null;

            // Формируем правильную детальную ссылку
            $detailUrl = $this->buildDetailUrl($element, $parentId, $elements);

            $resultItems[] = [
                'id' => $item->getId(),
                'basket_code' => $basketCode,
                'product_id' => $productId,
                'parent_id' => $parentId,
                'name' => $element['NAME'] ?? $item->getField('NAME'),
                'quantity' => $item->getQuantity(),
                'currency' => $item->getCurrency(),
                'product_type' => $productTypes[$productId] ?? null,
                'detail_url' => $detailUrl,
                'offer_url' => $parentId ? $detailUrl . '?offer=' . $productId : null,
                'image' => $this->getImage($element, $elements[$parentId] ?? null),
                'delay' => $item->isDelay(),
                'can_buy' => $item->canBuy(),
                'measure_ratio' => $ratioData[$basketCode] ?? 1.0,
                // null — остаток не ограничен, 0 — товара нет в наличии
                'available_quantity' => $this->getMaxQuantity((int) $productId),
                'props' => $this->getItemProperties($item),
                'prices' => $this->buildPriceData($item),
            ];
        }

        return [
            'items' => $resultItems,
            'summary' => $this->getSummary(),
            'coupons' => $this->getCoupons(),
        ];
    }

    public function getSummary(): array
    {
        $this->initializeDiscounts();

        $basePrice = PriceMaths::roundPrecision($this->basket->getBasePrice());
        $finalPrice = PriceMaths::roundPrecision($this->basket->getPrice());
        $discountValue = $basePrice - $finalPrice;
        $vatSum = PriceMaths::roundPrecision($this->basket->getVatSum());
        $weight = $this->basket->getWeight();

        $currency = CurrencyManager::getBaseCurrency();

        $discountPercent = $basePrice > 0
            ? round(($discountValue / $basePrice) * 100, 2)
            : 0;

        return [
            'count' => $this->getTotalQuantity(),
            'orderable_count' => $this->getOrderableQuantity(),
            'base' => $basePrice,
            'base_formatted' => $this->formatPrice($basePrice, $currency),
            'final' => $finalPrice,
            'final_formatted' => $this->formatPrice($finalPrice, $currency),

            'discount' => [
                'value' => $discountValue,
                'value_formatted' => $this->formatPrice($discountValue, $currency),
                'percent' => $discountPercent,
                'has_discounts' => !empty($this->discountData['applied_list']),
            ],

            'vat' => [
                'value' => $vatSum,
                'value_formatted' => $this->formatPrice($vatSum, $currency),
                'without_vat' => $finalPrice - $vatSum,
                'without_vat_formatted' => $this->formatPrice($finalPrice - $vatSum, $currency),
            ],

            'weight' => [
                'value' => $weight,
                'formatted' => $this->formatWeight($weight),
            ],

            'currency' => $currency,
        ];
    }

    /*=====================================
    =        QUANTITY LOGIC               =
    =====================================*/

    /**
     * Приводит количество к допустимому:
     *  1) кратно шагу (ближайшее, минимум один шаг);
     *  2) не больше остатка (если учёт остатков включён и покупка в минус запрещена).
     */
    private function normalizeQuantity(int $productId, float $quantity): float
    {
        $ratio = $this->getProductRatio($productId);

        $steps = max(1, (int) round($quantity / $ratio));
        $normalized = round($steps * $ratio, 4);

        $max = $this->getMaxQuantity($productId);

        if ($max !== null && $normalized > $max) {
            // Максимальное количество, кратное шагу, не превышающее остаток
            $maxSteps = (int) floor(($max / $ratio) + 1e-9);

            if ($maxSteps < 1) {
                throw new SystemException('Товара нет в наличии в нужном количестве');
            }

            $normalized = round($maxSteps * $ratio, 4);
        }

        return $normalized;
    }

    /**
     * Максимально доступное количество или null, если остаток не ограничен.
     *
     * Доступное количество — складской остаток за вычетом зарезервированного
     * (резервы других заказов продавать нельзя).
     */
    private function getMaxQuantity(int $productId): ?float
    {
        $product = ProductTable::getList([
            'filter' => ['=ID' => $productId],
            'select' => ['QUANTITY', 'QUANTITY_RESERVED', 'QUANTITY_TRACE', 'CAN_BUY_ZERO'],
        ])->fetch();

        if (!$product) {
            return 0.0;
        }

        $limited = ($product['QUANTITY_TRACE'] === 'Y' && $product['CAN_BUY_ZERO'] === 'N');

        if (!$limited) {
            return null;
        }

        $available = (float) $product['QUANTITY'] - (float) $product['QUANTITY_RESERVED'];

        return max(0.0, $available);
    }

    /**
     * Шаг (коэффициент единицы измерения) товара, по умолчанию 1.
     */
    private function getProductRatio(int $productId): float
    {
        $row = Catalog\MeasureRatioTable::getList([
            'filter' => ['=PRODUCT_ID' => $productId, '=IS_DEFAULT' => 'Y'],
            'select' => ['RATIO'],
            'limit' => 1,
        ])->fetch();

        $ratio = $row ? (float) $row['RATIO'] : 1.0;

        return $ratio > 0 ? $ratio : 1.0;
    }

    /**
     * Устанавливает количество и проверяет результат
     * (провайдер может вернуть ошибку, например при нехватке остатков).
     */
    private function changeQuantity(Sale\BasketItem $item, float $quantity): void
    {
        $result = $item->setField('QUANTITY', round($quantity, 4));

        if (!$result->isSuccess()) {
            throw new SystemException(implode(', ', $result->getErrorMessages()));
        }
    }

    private function getItemOrFail(string|int $basketItemId): Sale\BasketItem
    {
        $item = $this->basket->getItemById((int) $basketItemId);

        if (!$item) {
            throw new SystemException('Позиция корзины не найдена');
        }

        return $item;
    }

    /**
     * Фактическое состояние позиции после изменения.
     */
    private function itemState(Sale\BasketItem $item): array
    {
        return [
            'id' => (int) $item->getId(),
            'quantity' => (float) $item->getQuantity(),
            'deleted' => false,
        ];
    }

    /*=====================================
    =           PARENT & URL LOGIC        =
    =====================================*/

    /**
     * Загружает карту соответствия офферов и их родителей
     */
    private function loadSkuParentMap(array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        $productList = \CCatalogSku::getProductList($productIds);

        if (!empty($productList)) {
            foreach ($productList as $offerId => $offerInfo) {
                $this->skuParentMap[$offerId] = (int) $offerInfo['ID'];
            }
        }
    }

    /**
     * Загружает типы продуктов
     */
    private function loadProductTypes(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $types = [];
        $rows = ProductTable::getList([
            'filter' => ['=ID' => $productIds],
            'select' => ['ID', 'TYPE']
        ])->fetchAll();

        foreach ($rows as $row) {
            $types[$row['ID']] = (int) $row['TYPE'];
        }

        return $types;
    }

    /**
     * Строит карту URL для элементов
     */
    private function buildElementUrlMap(array $elements): void
    {
        foreach ($elements as $element) {
            $this->elementUrlMap[$element['ID']] = $element['DETAIL_PAGE_URL'] ?? '';
        }
    }

    /**
     * Формирует правильную детальную ссылку.
     * Для офферов возвращает ссылку на родительский товар.
     */
    private function buildDetailUrl(?array $element, ?int $parentId, array $allElements): string
    {
        if ($parentId && isset($allElements[$parentId])) {
            return $allElements[$parentId]['DETAIL_PAGE_URL'] ?? '';
        }

        return $element['DETAIL_PAGE_URL'] ?? '';
    }

    /**
     * Получает изображение товара.
     * Для офферов сначала проверяет свое изображение, потом родительское.
     */
    private function getImage(?array $element, ?array $parentElement): ?array
    {
        $imageId = null;

        if ($element) {
            $imageId = $element['PREVIEW_PICTURE'] ?: $element['DETAIL_PICTURE'];
        }

        if (!$imageId && $parentElement) {
            $imageId = $parentElement['PREVIEW_PICTURE'] ?: $parentElement['DETAIL_PICTURE'];
        }

        if (!$imageId) {
            return null;
        }

        $resizedImage = CFile::ResizeImageGet(
            $imageId,
            ['width' => 250, 'height' => 250],
            BX_RESIZE_IMAGE_PROPORTIONAL,
            true,
            []
        );

        $originImage = CFile::GetByID($imageId)?->Fetch() ?? [];

        return [
            'resized' => $resizedImage,
            'origin' => array_change_key_case($originImage, CASE_LOWER),
        ];
    }

    /**
     * Получает свойства элемента корзины
     */
    private function getItemProperties(Sale\BasketItem $item): array
    {
        $properties = [];
        $propertyCollection = $item->getPropertyCollection();

        foreach ($propertyCollection as $property) {
            $props = $property->getFieldValues();

            // Исключаем служебные свойства
            if (in_array($props['CODE'], ['CATALOG.XML_ID', 'PRODUCT.XML_ID'])) {
                continue;
            }

            $properties[] = [
                'name' => $props['NAME'],
                'code' => $props['CODE'],
                'value' => $props['VALUE'],
                'sort' => $props['SORT'],
            ];
        }

        return $properties;
    }

    /*=====================================
    =           PRICE LOGIC               =
    =====================================*/

    /**
     * Считает правила работы с корзиной и применяет цены для показа.
     *
     * Для корзины без заказа штатный путь — Discount::buildFromBasket():
     * calculate() запускает правила (включая купоны), getShowPrices() отдаёт
     * рассчитанные цены позиций. Временный Order здесь не нужен: у нового
     * заказа скидки не рассчитываются, поэтому купоны не влияли на цены.
     */
    private function initializeDiscounts(): void
    {
        if ($this->discountsCalculated) {
            return;
        }

        $this->discountsCalculated = true;
        $this->discountData = [
            'full_list' => [],
            'applied_list' => [],
        ];

        if ($this->basket->count() === 0) {
            return;
        }

        $context = new Sale\Discount\Context\Fuser($this->basket->getFUserId());
        $discounts = Sale\Discount::buildFromBasket($this->basket, $context);

        if ($discounts === null) {
            return;
        }

        $calcResult = $discounts->calculate();

        if (!$calcResult->isSuccess()) {
            throw new SystemException(
                implode(', ', $calcResult->getErrorMessages())
            );
        }

        $showPrices = $discounts->getShowPrices();

        if (!empty($showPrices['BASKET'])) {
            foreach ($showPrices['BASKET'] as $basketCode => $data) {
                $basketItem = $this->basket->getItemByBasketCode($basketCode);

                if ($basketItem) {
                    $basketItem->setFieldNoDemand('BASE_PRICE', $data['SHOW_BASE_PRICE']);
                    $basketItem->setFieldNoDemand('PRICE', $data['SHOW_PRICE']);
                    $basketItem->setFieldNoDemand('DISCOUNT_PRICE', $data['SHOW_DISCOUNT']);
                }
            }
        }

        $calcResults = $discounts->getApplyResult(true);
        $appliedList = [];

        foreach (($calcResults['DISCOUNT_LIST'] ?? []) as $discount) {
            if (($discount['APPLY'] ?? 'N') === 'Y') {
                $appliedList[] = $discount;
            }
        }

        $this->discountData = [
            'full_list' => $calcResults['FULL_DISCOUNT_LIST'] ?? [],
            'applied_list' => $appliedList,
        ];
    }

    private function buildPriceData(Sale\BasketItem $item): array
    {
        $currency = $item->getCurrency();

        $basePrice = PriceMaths::roundPrecision((float) $item->getBasePrice());
        $finalPrice = PriceMaths::roundPrecision((float) $item->getPrice());
        $discountPrice = PriceMaths::roundPrecision((float) $item->getDiscountPrice());

        $discountPercent = 0;
        if ($basePrice > 0 && $discountPrice > 0 && $item->getField('CUSTOM_PRICE') !== 'Y') {
            $percent = Sale\Discount::calculateDiscountPercent($basePrice, $discountPrice);
            $discountPercent = $percent ? round($percent, 2) : 0;
        }

        $quantity = $item->getQuantity();

        $sumBase = $basePrice * $quantity;
        $sumFinal = $finalPrice * $quantity;
        $sumDiscount = $discountPrice * $quantity;

        return [
            'price' => [
                'base' => $basePrice,
                'base_formatted' => $this->formatPrice($basePrice, $currency),
                'final' => $finalPrice,
                'final_formatted' => $this->formatPrice($finalPrice, $currency),
            ],

            'discount' => [
                'value' => $discountPrice,
                'value_formatted' => $this->formatPrice($discountPrice, $currency),
                'percent' => $discountPercent,
            ],

            'sum' => [
                'base' => $sumBase,
                'base_formatted' => $this->formatPrice($sumBase, $currency),
                'final' => $sumFinal,
                'final_formatted' => $this->formatPrice($sumFinal, $currency),
                'discount' => $sumDiscount,
                'discount_formatted' => $this->formatPrice($sumDiscount, $currency),
            ],

            'vat' => [
                'rate' => (float) $item->getVatRate(),
                'value' => $this->calculateVatValue($item),
            ],
        ];
    }

    /*=====================================
    =           INTERNAL HELPERS          =
    =====================================*/

    private function refresh(): void
    {
        $refreshStrategy = Sale\Basket\RefreshFactory::create(
            Sale\Basket\RefreshFactory::TYPE_FULL
        );

        $result = $this->basket->refresh($refreshStrategy);

        if (!$result->isSuccess()) {
            throw new SystemException(
                implode(', ', $result->getErrorMessages())
            );
        }
    }

    private function save(): void
    {
        if ($this->basket->isChanged()) {
            $result = $this->basket->save();

            if (!$result->isSuccess()) {
                throw new SystemException(
                    implode(', ', $result->getErrorMessages())
                );
            }
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

    private function getOrderableQuantity(): int
    {
        $count = 0;

        foreach ($this->basket as $item) {
            if ($item->canBuy() && !$item->isDelay()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Коэффициенты единиц измерения по коду позиции корзины.
     *
     * Глобальный getRatio() ожидает в каждом элементе PRODUCT_ID/MODULE/
     * PRODUCT_PROVIDER_CLASS, которых в подготовленном массиве не было,
     * поэтому шаг всегда сводился к 1. Штатный хелпер сам читает ratio
     * из каталога и кэширует результат.
     *
     * @return array<string, float>
     */
    private function getRatioData(): array
    {
        $result = Sale\BasketComponentHelper::getRatio($this->basket);

        if (!$result->isSuccess()) {
            return [];
        }

        $ratioList = $result->get('RATIO_LIST') ?: [];

        return array_map(
            static fn($ratio) => (float) $ratio,
            $ratioList
        );
    }

    private function calculateVatValue(Sale\BasketItem $item): float
    {
        $vatRate = (float) $item->getVatRate();

        if ($vatRate <= 0) {
            return 0.0;
        }

        $price = (float) $item->getPrice();

        // НДС уже включён в цену: выделяем его из цены
        if ($item->isVatInPrice()) {
            $vat = $price / (1 + $vatRate) * $vatRate;
        } else {
            // НДС сверху: начисляем его на цену
            $vat = $price * $vatRate;
        }

        return PriceMaths::roundPrecision($vat);
    }

    private function loadElements(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $rows = ElementTable::getList([
            'filter' => ['=ID' => $ids],
            'select' => [
                'ID',
                'NAME',
                'IBLOCK_ID',
                'IBLOCK_SECTION_ID',
                'PREVIEW_PICTURE',
                'DETAIL_PICTURE',
                'CODE',
                'XML_ID',
            ]
        ])->fetchAll();

        if (empty($rows)) {
            return [];
        }

        // Собираем ID инфоблоков для получения их шаблонов URL
        $iblockIds = array_unique(array_column($rows, 'IBLOCK_ID'));
        $iblockUrlTemplates = $this->loadIblockUrlTemplates($iblockIds);

        $result = [];
        foreach ($rows as $row) {
            $row['DETAIL_PAGE_URL'] = $iblockUrlTemplates[$row['IBLOCK_ID']] ?? '';

            if (!empty($row['DETAIL_PAGE_URL'])) {
                $row['DETAIL_PAGE_URL'] = \CIBlock::ReplaceDetailUrl(
                    $row['DETAIL_PAGE_URL'],
                    $row,
                    false,
                    'E'
                );
            }

            $result[$row['ID']] = $row;
        }

        return $result;
    }

    private function loadIblockUrlTemplates(array $iblockIds): array
    {
        if (empty($iblockIds)) {
            return [];
        }

        $templates = [];

        $res = \CIBlock::GetList([], ['ID' => $iblockIds]);
        while ($iblock = $res->Fetch()) {
            $templates[$iblock['ID']] = $iblock['DETAIL_PAGE_URL'];
        }

        return $templates;
    }

    private function formatPrice(float $price, string $currency): string
    {
        return \CCurrencyLang::CurrencyFormat($price, $currency, true);
    }

    private function formatWeight(float $weight): string
    {
        $weightKoef = (float) Option::get('sale', 'weight_koef', 1, SITE_ID);
        $weightUnit = Option::get('sale', 'weight_unit', '', SITE_ID);

        if ($weightKoef <= 0) {
            $weightKoef = 1;
        }

        return roundEx($weight / $weightKoef, SALE_WEIGHT_PRECISION) . ' ' . $weightUnit;
    }

    private function getCouponStatus(array $coupon): array
    {
        $statusMap = [
            DiscountCouponsManager::STATUS_NOT_FOUND => ['code' => 'NOT_FOUND', 'text' => 'Купон не найден'],
            DiscountCouponsManager::STATUS_ENTERED => ['code' => 'ENTERED', 'text' => 'Купон введен'],
            DiscountCouponsManager::STATUS_APPLYED => ['code' => 'APPLIED', 'text' => 'Купон применен'],
            DiscountCouponsManager::STATUS_NOT_APPLYED => ['code' => 'NOT_APPLIED', 'text' => 'Купон не применен'],
            DiscountCouponsManager::STATUS_FREEZE => ['code' => 'FREEZE', 'text' => 'Купон заморожен'],
        ];

        return $statusMap[$coupon['STATUS']] ?? ['code' => 'UNKNOWN', 'text' => 'Неизвестный статус'];
    }

    private function getAvailableColumns(): array
    {
        // Для совместимости с компонентом
        return ['DELETE', 'DELAY', 'QUANTITY', 'PRICE'];
    }
}