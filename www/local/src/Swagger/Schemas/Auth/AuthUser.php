<?php

namespace App\Swagger\Schemas\Auth;

use OpenApi\Spec as OA;

#[OA\Schema]
final class AuthUser
{
    #[OA\Property(example: 42)]
    public int $id;

    #[OA\Property(example: 'ivanov')]
    public string $login;

    #[OA\Property(example: 'ivanov@example.com')]
    public string $email;

    #[OA\Property(example: 'Иван Иванов')]
    public string $name;

    #[OA\Property(example: '+79001234567')]
    public string $phone;
}
