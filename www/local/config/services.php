<?php

use Bitrix\Main\DI\ServiceLocator;
use Lib\Auth\AuthService;

$locator = ServiceLocator::getInstance();

/**
 * AuthService
 */
$locator->addInstanceLazy(
    AuthService::class,
    [
        'className' => AuthService::class,
    ]
);