<?php
namespace Controllers;

use Bitrix\Main\Engine\Controller;
use Lib\Auth\AuthService;

class AuthController extends Controller
{



    protected function getDefaultPreFilters(): array
    {
        return [

        ];
    }

    public function loginAction(AuthService $authService)
    {
        return $authService->login();
    }
}
