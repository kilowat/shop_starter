<?php
namespace Wigital\Lib\Basket\Controllers;

use Bitrix\Main\Engine\Controller;
use Wigital\Lib\Basket\Services\BasketService;

final class Basketontroller extends Controller
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

    public function loginAction(BasketService $service)
    {
        return $service->login();
    }
}
