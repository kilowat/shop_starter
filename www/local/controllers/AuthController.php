<?php

namespace Controllers;

use Bitrix\Main\Engine\Controller;
use OpenApi\Spec as OA;
use Services\Auth\AuthService;

final class AuthController extends Controller
{
    protected function getDefaultPreFilters(): array
    {

        return [];
    }
    public function loginAction(AuthService $authService)
    {
        return $authService->login();
    }
}
