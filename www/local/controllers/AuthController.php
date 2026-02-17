<?php
namespace Controllers;

use Bitrix\Main\Engine\Controller;

class AuthController extends Controller
{
    protected function getDefaultPreFilters(): array
    {
        return [

        ];
    }

    public function loginAction()
    {
        return ['auth login'];
    }
}
