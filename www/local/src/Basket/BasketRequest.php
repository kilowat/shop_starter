<?php

namespace App\Basket;

use App\Helper\Http\Mapper\RequestMapper;



final readonly class BasketRequest
{
    use RequestMapper;

    public function __construct(
        public string|int $productId = 0,
        public string|int $basketId = 0,
        public float $quantity = 1,
        public bool $delay = false,
        public array $props = [],
        public string $coupon = '',
    ) {
    }
}