<?php

namespace App\PageState;

use App\Basket\BasketStateService;

final class PageStateService
{
    public function get(): array
    {
        return [
            'v' => 1,
            'basket' => $this->basket(),
            'favorites' => $this->favorites(),
        ];
    }

    private function basket(): array
    {
        $state = (new BasketStateService())->get();

        return [
            'count' => (float) ($state['count'] ?? 0),
            'productIds' => array_values(array_map('intval', $state['productIds'] ?? [])),
        ];
    }

    private function favorites(): array
    {
        return [
            'ids' => [],
        ];
    }
}