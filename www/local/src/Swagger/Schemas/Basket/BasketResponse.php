<?php

namespace App\Swagger\Schemas\Basket;

use OpenApi\Spec as OA;
use App\Swagger\Schemas\ApiError;

#[OA\Schema]
final class BasketResponse
{
    #[OA\Property(example: 'success')]
    public string $status;

    #[OA\Property]
    #[OA\Schema(ref: Basket::class)]
    public Basket $data;

    #[OA\Property]
    #[OA\Schema(
        type: 'array',
        items: new OA\Schema(ref: ApiError::class),
    )]
    public array $errors;
}