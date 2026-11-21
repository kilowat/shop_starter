<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use App\Basket\BasketStateService;

class BasketSeedComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        $state = (new BasketStateService())->get();

        $this->arResult = [
            'COUNT' => (float) ($state['count'] ?? 0),
            'PRODUCT_IDS' => array_values(array_map('intval', $state['productIds'] ?? [])),
        ];
        $this->arResult['SEED'] = [
            'count' => $this->arResult['COUNT'],
            'productIds' => $this->arResult['PRODUCT_IDS'],
        ];

        $this->includeComponentTemplate();

        return $this->arResult;
    }
}