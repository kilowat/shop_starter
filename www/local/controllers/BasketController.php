<?php
namespace Controllers;

use Bitrix\Main\Engine\Controller;
use Services\Basket\BasketService;

final class BasketController extends Controller
{
    protected function getDefaultPreFilters(): array
    {
        return [

        ];
    }

    public function showAction(BasketService $basketService)
    {
        return $basketService->getData();
    }
}
