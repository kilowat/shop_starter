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
    #[OA\Property(example: 'Некорректные параметры')]
    public string $message;

    #[OA\Property(example: 'INVALID_PARAMS')]
    public string $code;
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