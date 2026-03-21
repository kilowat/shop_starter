<?php


use Bitrix\Main\Routing\Controllers\PublicPageController;
use Bitrix\Main\Routing\RoutingConfigurator;



return static function (RoutingConfigurator $routes) {

    $routes->get('/api/auth/login', [Controllers\AuthController::class, 'login']);
    $routes->get('/api/basket', [Controllers\BasketController::class, 'show']);
    $routes->get('/catalog/', function () {
        return ['test'];
    });
    /*
    $routes->get('/api/auth/login', static function () {
        return Components\CatalogSection::forCatalogList()->fromRequest()->getResponse();
    });
    */
};
