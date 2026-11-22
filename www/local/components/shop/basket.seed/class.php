<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use App\PageState\PageStateService;

class BasketSeedComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        $state = $this->arParams['STATE'] ?? null;

        if (!is_array($state)) {
            $state = (new PageStateService())->get();
        }

        $basket = is_array($state['basket'] ?? null) ? $state['basket'] : [];

        $this->arResult = [
            'COUNT' => (float) ($basket['count'] ?? 0),
        ];

        $this->includeComponentTemplate();

        return $this->arResult;
    }
}