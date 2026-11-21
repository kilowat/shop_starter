<?php

namespace App\Basket;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Loader;
use Bitrix\Sale\Fuser;

final class BasketStateService
{
    public function get(): array
    {
        $state = [
            'count' => 0.0,
            'productIds' => [],
        ];

        if (!Loader::includeModule('sale')) {
            return $state;
        }

        $fuserId = (int) Fuser::getId();

        if ($fuserId <= 0) {
            return $state;
        }

        $connection = Application::getConnection();
        $sql = (new SqlExpression(
            'SELECT PRODUCT_ID, QUANTITY FROM ?# WHERE FUSER_ID = ?i AND LID = ?s AND ORDER_ID IS NULL',
            'b_sale_basket',
            $fuserId,
            (string) SITE_ID
        ))->compile();

        $productIds = [];

        foreach ($connection->query($sql) as $row) {
            $state['count'] += (float) $row['QUANTITY'];

            $productId = (int) $row['PRODUCT_ID'];

            if ($productId > 0) {
                $productIds[$productId] = $productId;
            }
        }

        $state['productIds'] = $this->withParentIds(array_values($productIds));

        return $state;
    }

    private function withParentIds(array $productIds): array
    {
        if ($productIds === [] || !Loader::includeModule('catalog') || !class_exists('CCatalogSku')) {
            return $productIds;
        }

        $productList = \CCatalogSku::getProductList($productIds);

        if (empty($productList)) {
            return $productIds;
        }

        $parentIds = [];

        foreach ($productList as $offerInfo) {
            $parentId = (int) ($offerInfo['ID'] ?? 0);

            if ($parentId > 0) {
                $parentIds[$parentId] = $parentId;
            }
        }

        return array_values(array_unique(array_merge($productIds, array_values($parentIds))));
    }
}