<?php

use Bitrix\Main\Routing\RoutingConfigurator;
use Controllers\BasketController;
use Controllers\AuthController;

return static function (RoutingConfigurator $routes): void {

    $routes->get('/api/basket', [BasketController::class, 'show']);

    $routes->post('/api/basket/items', [BasketController::class, 'add']);
    $routes->post('/api/basket/items/quantity', [BasketController::class, 'updateQuantity']);
    $routes->post('/api/basket/items/delay', [BasketController::class, 'setDelay']);
    $routes->post('/api/basket/items/remove', [BasketController::class, 'remove']);
    $routes->post('/api/basket/clear', [BasketController::class, 'clear']);
    $routes->post('/api/basket/coupon', [BasketController::class, 'applyCoupon']);
    $routes->post('/api/basket/coupon/remove', [BasketController::class, 'removeCoupon']);

    $routes->get('/api/auth/login', [AuthController::class, 'login']);
};