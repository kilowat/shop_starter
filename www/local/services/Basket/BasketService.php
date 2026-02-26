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
use Bitrix\Sale\PriceMaths;

class BasketService
{
    private Sale\Basket $basket;
    private ?Sale\Order $order = null;
    private array $discountData = [];
    private array $skuParentMap = []; // Карта соответствия оффер -> родитель
    private array $elementUrlMap = []; // Карта URL элементов

    public function __construct()
    {
        Loader::includeModule('sale');
        Loader::includeModule('catalog');
        Loader::includeModule('iblock');
        Loader::includeModule('currency');

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

    public function add(int $productId, float $quantity = 1, array $props = []): void
    {
        $items = $this->basket->getExistsItems('catalog', $productId);

        if (!empty($items)) {
            foreach ($items as $item) {
                $item->setField('QUANTITY', $item->getQuantity() + $quantity);
            }
        } else {
            $item = $this->basket->createItem('catalog', $productId);

            $fields = [
                'QUANTITY' => $quantity,
                'CURRENCY' => CurrencyManager::getBaseCurrency(),
                'LID' => SITE_ID,
                'PRODUCT_PROVIDER_CLASS' => Catalog\Product\CatalogProvider::class,
            ];

            // Добавляем свойства товара если есть (для офферов)
            if (!empty($props)) {
                $fields['PROPS'] = $props;
            }

            $item->setFields($fields);
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

    public function removeById(int $basketId): void
    {
        $item = $this->basket->getItemById($basketId);

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

    public function updateQuantity(int $basketId, float $quantity): void
    {
        $item = $this->basket->getItemById($basketId);

        if ($item) {
            $item->setField('QUANTITY', $quantity);
            $this->refresh();
            $this->save();
        }
    }

    public function setDelay(int $basketId, bool $delay = true): void
    {
        $item = $this->basket->getItemById($basketId);

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
        // Создаем заказ для применения всех скидок
        $this->initializeOrder();

        // Обновляем корзину
        $this->refresh();

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
        $ratioData = $this->getRatioData($items);

        $resultItems = [];

        foreach ($items as $item) {
            $productId = $item->getProductId();
            $element = $elements[$productId] ?? null;
            $basketCode = $item->getBasketCode();

            // Определяем parent_id (для офферов)
            $parentId = $this->skuParentMap[$productId] ?? null;

            // Формируем правильную детальную ссылку
            $detailUrl = $this->buildDetailUrl($element, $parentId, $elements);

            $resultItems[] = array_merge([
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
                'measure_ratio' => $ratioData[$basketCode]['MEASURE_RATIO'] ?? 1,
                'available_quantity' => $this->getAvailableQuantity($item),
                'props' => $this->getItemProperties($item),
            ], $this->buildPriceData($item));
        }

        return [
            'items' => $resultItems,
            'summary' => $this->getSummary(),
            'coupons' => $this->getCoupons(),
        ];
    }

    public function getSummary(): array
    {
        $this->initializeOrder();

        $basePrice = PriceMaths::roundPrecision($this->basket->getBasePrice());
        $finalPrice = PriceMaths::roundPrecision($this->basket->getPrice());
        $discountValue = $basePrice - $finalPrice;
        $weight = $this->basket->getWeight();
        $vatSum = PriceMaths::roundPrecision($this->basket->getVatSum());

        $discountPercent = $basePrice > 0
            ? round(($discountValue / $basePrice) * 100, 2)
            : 0;

        $currency = CurrencyManager::getBaseCurrency();

        return [
            'count' => $this->getTotalQuantity(),
            'orderable_count' => $this->getOrderableQuantity(),
            'base_price' => $basePrice,
            'base_price_formatted' => $this->formatPrice($basePrice, $currency),
            'final_price' => $finalPrice,
            'final_price_formatted' => $this->formatPrice($finalPrice, $currency),
            'discount_value' => $discountValue,
            'discount_value_formatted' => $this->formatPrice($discountValue, $currency),
            'discount_percent' => $discountPercent,
            'weight' => $weight,
            'weight_formatted' => $this->formatWeight($weight),
            'vat_sum' => $vatSum,
            'vat_sum_formatted' => $this->formatPrice($vatSum, $currency),
            'price_without_vat' => $finalPrice - $vatSum,
            'price_without_vat_formatted' => $this->formatPrice($finalPrice - $vatSum, $currency),
            'currency' => $currency,
            'has_discounts' => !empty($this->discountData['applied_list']),
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
     * Формирует правильную детальную ссылку
     * Для офферов возвращает ссылку на родительский товар
     */
    private function buildDetailUrl(?array $element, ?int $parentId, array $allElements): string
    {
        // Если есть родитель, используем его URL
        if ($parentId && isset($allElements[$parentId])) {
            return $allElements[$parentId]['DETAIL_PAGE_URL'] ?? '';
        }

        // Иначе используем URL самого элемента
        return $element['DETAIL_PAGE_URL'] ?? '';
    }

    /**
     * Получает изображение товара
     * Для офферов сначала проверяет свое изображение, потом родительское
     */
    private function getImage(?array $element, ?array $parentElement): ?string
    {
        $imageId = null;

        if ($element) {
            $imageId = $element['PREVIEW_PICTURE'] ?: $element['DETAIL_PICTURE'];
        }

        // Если у оффера нет изображения, берем у родителя
        if (!$imageId && $parentElement) {
            $imageId = $parentElement['PREVIEW_PICTURE'] ?: $parentElement['DETAIL_PICTURE'];
        }

        if (!$imageId) {
            return null;
        }

        return \CFile::GetPath($imageId);
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

    private function initializeOrder(): void
    {
        if (!$this->basket->getOrder()) {
            $registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
            /** @var Sale\Order $orderClass */
            $orderClass = $registry->getOrderClassName();

            global $USER;
            $userId = $USER && $USER->IsAuthorized() ? (int) $USER->GetID() : 0;

            $this->order = $orderClass::create(SITE_ID, $userId);
            $this->order->appendBasket($this->basket);

            // Получаем цены с примененными скидками
            $discounts = $this->order->getDiscount();
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

            // Получаем информацию о примененных скидках
            $calcResults = $discounts->getApplyResult(true);
            $this->discountData = [
                'full_list' => $calcResults['FULL_DISCOUNT_LIST'] ?? [],
                'applied_list' => $calcResults['APPLIED_DISCOUNT_LIST'] ?? [],
            ];
        }
    }

    private function buildPriceData(Sale\BasketItem $item): array
    {
        $basePrice = PriceMaths::roundPrecision((float) $item->getBasePrice());
        $finalPrice = PriceMaths::roundPrecision((float) $item->getPrice());
        $discountPrice = PriceMaths::roundPrecision((float) $item->getDiscountPrice());

        $discountPercent = 0;
        if ($basePrice > 0 && $discountPrice > 0 && $item->getField('CUSTOM_PRICE') !== 'Y') {
            $discountPercent = Sale\Discount::calculateDiscountPercent($basePrice, $discountPrice);
            $discountPercent = $discountPercent === null ? 0 : round($discountPercent, 2);
        }

        // Сумма с учетом количества
        $sumValue = $finalPrice * $item->getQuantity();
        $sumBaseValue = $basePrice * $item->getQuantity();
        $sumDiscountValue = $discountPrice * $item->getQuantity();

        return [
            'base_price' => $basePrice,
            'base_price_formatted' => $this->formatPrice($basePrice, $item->getCurrency()),
            'final_price' => $finalPrice,
            'final_price_formatted' => $this->formatPrice($finalPrice, $item->getCurrency()),
            'discount_price' => $discountPrice,
            'discount_price_formatted' => $this->formatPrice($discountPrice, $item->getCurrency()),
            'discount_percent' => $discountPercent,
            'sum' => $sumValue,
            'sum_formatted' => $this->formatPrice($sumValue, $item->getCurrency()),
            'sum_base' => $sumBaseValue,
            'sum_base_formatted' => $this->formatPrice($sumBaseValue, $item->getCurrency()),
            'sum_discount' => $sumDiscountValue,
            'sum_discount_formatted' => $this->formatPrice($sumDiscountValue, $item->getCurrency()),
            'vat_rate' => (float) $item->getVatRate(),
            'vat_value' => $this->calculateVatValue($item),
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

    private function getRatioData(array $items): array
    {
        $data = [];

        foreach ($items as $item) {
            $data[$item->getBasketCode()] = [
                'MEASURE_RATIO' => $item->getField('MEASURE_RATIO') ?: 1,
            ];
        }

        return getRatio($data);
    }

    private function getAvailableQuantity(Sale\BasketItem $item): float
    {
        if (!Loader::includeModule('catalog')) {
            return 0;
        }

        $product = Catalog\ProductTable::getList([
            'filter' => ['=ID' => $item->getProductId()],
            'select' => ['QUANTITY', 'QUANTITY_TRACE', 'CAN_BUY_ZERO']
        ])->fetch();

        if (!$product) {
            return 0;
        }

        $checkMax = ($product['QUANTITY_TRACE'] == 'Y' && $product['CAN_BUY_ZERO'] == 'N');

        return $checkMax ? (float) $product['QUANTITY'] : 0;
    }

    private function calculateVatValue(Sale\BasketItem $item): float
    {
        $vatRate = (float) $item->getVatRate();
        if ($vatRate <= 0) {
            return 0;
        }

        $price = $item->getPrice();
        $quantity = $item->getQuantity();

        return PriceMaths::roundPrecision(
            ($price * $quantity / ($vatRate + 1)) * $vatRate / $quantity
        );
    }

    private function loadElements(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        // Получаем элементы с основными полями
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

        // Формируем результат с URL
        $result = [];
        foreach ($rows as $row) {
            // Добавляем шаблон URL из инфоблока
            $row['DETAIL_PAGE_URL'] = $iblockUrlTemplates[$row['IBLOCK_ID']] ?? '';

            // Формируем детальную ссылку используя шаблон инфоблока
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

        // Используем старый способ через CIBlock, так как в ORM тоже нет прямого поля DETAIL_PAGE_URL
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
        $weightKoef = (float) \COption::GetOptionString('sale', 'weight_koef', 1, SITE_ID);
        $weightUnit = \COption::GetOptionString('sale', 'weight_unit', '', SITE_ID);

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