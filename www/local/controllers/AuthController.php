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

    #[OA\Operation\Post(path: '/api/auth/login')]
    #[OA\Response(response: 200, description: 'Успешная авторизация')]
    #[OA\Response(response: 401, description: 'Неверный логин или пароль')]
    public function loginAction(AuthService $authService)
    {
        return $authService->login();
    }
}
