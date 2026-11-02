<?php

use Bitrix\Main\Routing\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {
    (require dirname(__DIR__) . '/src/Swagger/router.php')($routes);
};
