<?php


use Bitrix\Main\Routing\RoutingConfigurator;



return static function (RoutingConfigurator $routes) {

    $routes->get('/ajax/catalog', [Controllers\CatalogController::class, 'index']);
};
