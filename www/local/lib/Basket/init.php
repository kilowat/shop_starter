<?php

use Bitrix\Main\DI\ServiceLocator;
use Wigital\Lib\Basket\Services\BasketService;

$locator = ServiceLocator::getInstance();

/**
 * AuthService
 */
$locator->addInstanceLazy(
    BasketService::class,
    [
        'className' => BasketService::class,
    ]
);