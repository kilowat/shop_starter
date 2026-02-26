<?php
namespace Controllers;

use Bitrix\Main\Engine\Controller;
use Services\Auth\AuthService;

final class AuthController extends Controller
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

    public function loginAction(AuthService $authService)
    {
        return $authService->login();
    }
}
