<?php

namespace App\Swagger\Schemas\Basket;

use OpenApi\Spec as OA;

#[OA\Schema]
final class BasketSummary
{
    #[OA\Property(example: 3)]
    public int $items_count;

    #[OA\Property(example: 5)]
    public int $quantity;

    #[OA\Property]
    #[OA\Schema(type: 'number', format: 'float', example: 1500.00)]
    public float $subtotal;

    #[OA\Property]
    #[OA\Schema(type: 'number', format: 'float', example: 100.00)]
    public float $discount;

    #[OA\Property]
    #[OA\Schema(type: 'number', format: 'float', example: 1400.00)]
    public float $total;
}