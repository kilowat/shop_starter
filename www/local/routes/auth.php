<?php


use Bitrix\Main\Routing\RoutingConfigurator;
use Controllers\AuthController;



return function (RoutingConfigurator $routes) {

    $routes->get('/api/auth/login', [AuthController::class, 'login']);

};
