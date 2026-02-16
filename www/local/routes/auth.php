<?php

use Bitrix\Main\Routing\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    
    $routes->get('/api/auth/login', [Auth\AuthController::class, 'login']);
    
};
