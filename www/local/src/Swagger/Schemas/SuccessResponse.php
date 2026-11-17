<?php

namespace App\Swagger\Schemas;

use OpenApi\Spec as OA;

#[OA\Schema]
final class SuccessResponse
{
    #[OA\Property]
    #[OA\Schema(type: 'string', example: 'success')]
    public string $status;

    #[OA\Property]
    #[OA\Schema(
        type: 'object',
        nullable: true,
        example: null,
        description: 'Всегда null; данные текущего пользователя — GET /api/auth/me',
    )]
    public ?object $data;

    #[OA\Property]
    #[OA\Schema(
        type: 'array',
        items: new OA\Schema(ref: ApiError::class),
    )]
    public array $errors;
}