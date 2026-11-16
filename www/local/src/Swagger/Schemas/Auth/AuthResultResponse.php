<?php

namespace App\Swagger\Schemas\Auth;

use OpenApi\Spec as OA;
use App\Swagger\Schemas\ApiError;

#[OA\Schema]
final class AuthResultResponse
{
    #[OA\Property(example: 'success')]
    public string $status;

    #[OA\Property(
        property: 'data',
        schema: new OA\Schema(
            type: 'object',
            properties: [
                new OA\Property(
                    property: 'sent',
                    schema: new OA\Schema(type: 'boolean', example: true, description: 'Письмо отправлено'),
                ),
                new OA\Property(
                    property: 'changed',
                    schema: new OA\Schema(type: 'boolean', example: true, description: 'Пароль изменён'),
                ),
                new OA\Property(
                    property: 'logged_out',
                    schema: new OA\Schema(type: 'boolean', example: true, description: 'Выход выполнен'),
                ),
                new OA\Property(
                    property: 'channel',
                    schema: new OA\Schema(type: 'string', example: 'phone', description: 'Канал доставки кода: email или phone'),
                ),
            ],
        ),
    )]
    public array $data;

    #[OA\Property]
    #[OA\Schema(
        type: 'array',
        items: new OA\Schema(ref: ApiError::class),
    )]
    public array $errors;
}
