<?php

namespace App\Basket;

use App\Helper\Http\Mapper\Key;
use App\Helper\Http\Mapper\RequestMapper;

final class BasketRequest
{
    use RequestMapper;

    #[Key('productId')]
    public string|int $productId = 0;

    #[Key('basketId')]
    public string|int $basketId = 0;

    #[Key('quantity')]
    public float $quantity = 1;

    #[Key('delay')]
    public bool $delay = false;

    #[Key('props')]
    public array $props = [];

    #[Key('coupon')]
    public string $coupon = '';
}