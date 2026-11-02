<?php

namespace App\Swagger\Schemas;

use OpenApi\Spec as OA;

#[OA\Schema]
final class ApiError
{
    #[OA\Property(example: 'VALIDATION_ERROR')]
    public string $code;

    #[OA\Property(example: 'Некорректные данные')]
    public string $message;
}