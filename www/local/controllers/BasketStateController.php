<?php

namespace Controllers;

use App\Basket\BasketStateService;
use Bitrix\Main\Engine\ActionFilter;

final class BasketStateController extends ApiController
{
    protected function getDefaultPreFilters(): array
    {
        return [
            new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
        ];
    }

    public function stateAction(): array
    {
        return (new BasketStateService())->get();
    }
}