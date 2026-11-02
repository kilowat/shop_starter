<?php

namespace App\Swagger\Schemas\Basket;

use OpenApi\Spec as OA;

#[OA\Schema]
final class BasketCoupon
{
    #[OA\Property(example: 'SALE10')]
    public string $code;

    #[OA\Property]
    #[OA\Schema(type: 'number', format: 'float', example: 10.00)]
    public float $discount;

    #[OA\Property(example: true)]
    public bool $is_applied;
}