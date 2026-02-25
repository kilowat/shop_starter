<?php


use Bitrix\Main\Routing\RoutingConfigurator;
use Wigital\Lib\Auth\Controllers\AuthController;



return static function (RoutingConfigurator $routes) {

    $routes->get('/api/auth/login', [AuthController::class, 'login']);
    /*
    $routes->get('/api/auth/login', static function () {
        return Components\CatalogSection::forCatalogList()->fromRequest()->getResponse();
    });
    */
};
