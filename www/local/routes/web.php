<?php


use Bitrix\Main\Routing\RoutingConfigurator;



return static function (RoutingConfigurator $routes) {

    $routes->get('catalog', [Controllers\CatalogController::class, 'index']);
};
