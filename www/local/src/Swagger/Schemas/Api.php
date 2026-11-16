<?php

namespace App\Swagger\Schemas;

use OpenApi\Spec as OA;

#[OA\OpenApi(version: '3.1.0')]
#[OA\Info(title: 'API', version: '1.0')]
final class Api
{
}

#[OA\Schema]
final class ApiError
{
    #[OA\Property]
    #[OA\Schema(type: 'string', example: 'Некорректные параметры')]
    public string $message;

    #[OA\Property]
    #[OA\Schema(type: 'string', example: 'INVALID_PARAMS')]
    public string $code;

    #[OA\Property]
    #[OA\Schema(
        type: 'object',
        nullable: true,
        description: 'Дополнительные данные ошибки; например, параметры капчи для code = captcha_required',
        properties: [
            new OA\Property(property: 'captcha_sid', schema: new OA\Schema(type: 'string', example: '0a1b2c3d4e5f')),
            new OA\Property(property: 'captcha_url', schema: new OA\Schema(type: 'string', example: '/bitrix/tools/captcha.php?captcha_sid=0a1b2c3d4e5f')),
        ],
    )]
    public ?array $customData;
}

#[OA\Response(
    component: 'Error',
    description: 'Ошибка',
    content: new OA\Schema(
        type: 'object',
        properties: [
            new OA\Property(property: 'status', type: 'string', example: 'error'),
            new OA\Property(property: 'data', type: 'object', nullable: true),
            new OA\Property(
                property: 'errors',
                type: 'array',
                items: new OA\Schema(ref: ApiError::class)
            ),
        ]
    )
)]
final class ErrorResponse
{
}