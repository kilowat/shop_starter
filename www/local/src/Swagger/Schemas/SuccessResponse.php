<?php

namespace App\Swagger\Schemas;

use OpenApi\Spec as OA;

/**
 * Успешный ответ без данных: действия Auth/Profile ничего не возвращают,
 * текущий пользователь — GET /api/auth/me.
 */
#[OA\Schema]
final class SuccessResponse
{
    #[OA\Property(example: 'success')]
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
