<?php

use Bitrix\Main\Routing\RoutingConfigurator;
use Controllers\BasketController;
use Controllers\AuthController;
use Controllers\ProfileController;
use Controllers\CaptchaController;

return static function (RoutingConfigurator $routes): void {

    $routes->get('/api/basket', [BasketController::class, 'show']);

    $routes->post('/api/basket/items', [BasketController::class, 'add']);
    $routes->post('/api/basket/items/quantity', [BasketController::class, 'updateQuantity']);
    $routes->post('/api/basket/items/delay', [BasketController::class, 'setDelay']);
    $routes->post('/api/basket/items/remove', [BasketController::class, 'remove']);
    $routes->post('/api/basket/clear', [BasketController::class, 'clear']);
    $routes->post('/api/basket/coupon', [BasketController::class, 'applyCoupon']);
    $routes->post('/api/basket/coupon/remove', [BasketController::class, 'removeCoupon']);

    $routes->post('/api/auth/login', [AuthController::class, 'login']);
    $routes->post('/api/auth/register', [AuthController::class, 'register']);
    $routes->post('/api/auth/confirm', [AuthController::class, 'confirmRegistration']);
    $routes->post('/api/auth/logout', [AuthController::class, 'logout']);
    $routes->get('/api/auth/me', [AuthController::class, 'me']);
    $routes->post('/api/auth/password/forgot', [AuthController::class, 'forgotPassword']);
    $routes->post('/api/auth/password/reset', [AuthController::class, 'resetPassword']);

    $routes->get('/api/captcha', [CaptchaController::class, 'captcha']);

    $routes->post('/api/profile/update', [ProfileController::class, 'update']);
    $routes->post('/api/profile/delete', [ProfileController::class, 'delete']);
};