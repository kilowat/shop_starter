<?php

namespace Controllers;

use App\PageState\PageStateService;
use Bitrix\Main\Engine\ActionFilter;

final class PageStateController extends ApiController
{
    protected function getDefaultPreFilters(): array
    {
        return [
            new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
        ];
    }

    public function stateAction(): array
    {
        return (new PageStateService())->get();
    }
}