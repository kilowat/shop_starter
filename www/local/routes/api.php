<?php

use Bitrix\Main\Routing\RoutingConfigurator;

return function (RoutingConfigurator $routes) {
    $routes->get('/api/test', function () {
        return ['test'];
    });
    /*
    $routes->get('/test', [Local\Controllers\TestController::class, 'show'])
        ->name('home');
    */
};
