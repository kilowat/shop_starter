<?php

namespace App\Swagger\Schemas\Basket;

use OpenApi\Spec as OA;

#[OA\Schema]
final class Basket
{
    #[OA\Property]
    #[OA\Schema(
        type: 'array',
        items: new OA\Schema(ref: BasketItem::class),
    )]
    public array $items;

    #[OA\Property]
    #[OA\Schema(ref: BasketSummary::class)]
    public BasketSummary $summary;

    #[OA\Property]
    #[OA\Schema(
        oneOf: [
            new OA\Schema(ref: BasketCoupon::class),
            new OA\Schema(type: 'null'),
        ],
    )]
    public ?BasketCoupon $coupon;
}