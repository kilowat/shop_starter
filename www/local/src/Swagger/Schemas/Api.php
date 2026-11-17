<?php

namespace App\Swagger\Schemas;

use OpenApi\Spec as OA;

#[OA\OpenApi(version: '3.1.0')]
#[OA\Info(title: 'API', version: '1.0')]
final class Api
{
}

#[OA\Schema]
final class ErrorResponse
{
    #[OA\Property]
    #[OA\Schema(type: 'string', example: 'error')]
    public string $status;

    #[OA\Property]
    #[OA\Schema(type: 'object', nullable: true, example: null, description: 'Данные ответа; при ошибке всегда null')]
    public ?object $data;

    #[OA\Property]
    #[OA\Schema(
        type: 'array',
        items: new OA\Schema(ref: ApiError::class),
    )]
    public array $errors;
}