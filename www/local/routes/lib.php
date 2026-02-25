<?php

use Bitrix\Main\Routing\RoutingConfigurator;

return static function (RoutingConfigurator $routes) {

    foreach (glob($_SERVER['DOCUMENT_ROOT'] . '/local/lib/*/routes.php') as $file) {
        $callback = require $file;

        if (is_callable($callback)) {
            $callback($routes);
        }
    }

};