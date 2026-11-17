<?php

namespace App\Swagger\Schemas\Auth;

use OpenApi\Spec as OA;

#[OA\Schema]
final class AuthUser
{
    #[OA\Property]
    #[OA\Schema(type: 'integer', example: 42)]
    public int $id;

    #[OA\Property]
    #[OA\Schema(type: 'string', example: 'Иван', description: 'Имя пользователя (USER.NAME)')]
    public string $name;

    #[OA\Property]
    #[OA\Schema(type: 'string', example: 'Иван Иванов', description: 'Полное имя (имя и фамилия)')]
    public string $full_name;

    #[OA\Property]
    #[OA\Schema(type: 'string', format: 'email', example: 'ivanov@example.com')]
    public string $email;
}