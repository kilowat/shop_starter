<?php
namespace Controllers;

use Bitrix\Main\Engine\Controller;
use Services\Basket\BasketService;

final class BasketController extends Controller
{

    protected function init()
    {
        parent::init();

        // initialize services and/or load modules
    }
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
