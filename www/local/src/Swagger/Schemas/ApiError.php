<?php

namespace App\Swagger\Schemas;

use OpenApi\Spec as OA;

#[OA\Schema]
final class ApiError
{
    #[OA\Property]
    #[OA\Schema(type: 'string', example: 'VALIDATION_ERROR')]
    public string $code;

    #[OA\Property]
    #[OA\Schema(type: 'string', example: 'Некорректные данные')]
    public string $message;

    #[OA\Property]
    #[OA\Schema(
        type: 'object',
        nullable: true,
        description: 'Дополнительные данные ошибки (например, параметры капчи)',
        properties: [
            new OA\Property(property: 'captcha_sid', schema: new OA\Schema(type: 'string', example: '0a1b2c3d4e5f')),
            new OA\Property(property: 'captcha_url', schema: new OA\Schema(type: 'string', example: '/bitrix/tools/captcha.php?captcha_sid=0a1b2c3d4e5f')),
        ],
    )]
    public ?array $customData;
}