<?php

namespace App\Swagger\Schemas\Basket;

use OpenApi\Spec as OA;

#[OA\Schema]
final class BasketItem
{
    #[OA\Property(example: 123)]
    public int $id;

    #[OA\Property(example: 456)]
    public int $product_id;

    #[OA\Property(example: 'Парацетамол')]
    public string $name;

    #[OA\Property]
    #[OA\Schema(type: 'number', format: 'float', example: 150.50)]
    public float $price;

    #[OA\Property(example: 2)]
    public int $quantity;

    #[OA\Property]
    #[OA\Schema(type: 'number', format: 'float', example: 301.00)]
    public float $total;
}