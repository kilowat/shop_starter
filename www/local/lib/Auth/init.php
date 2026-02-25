<?php

use Bitrix\Main\DI\ServiceLocator;
use Wigital\Lib\Auth\Provider\EmailAuthProvider;
use Wigital\Lib\Auth\Services\AuthService;

$locator = ServiceLocator::getInstance();

/**
 * AuthService
 */
$locator->addInstanceLazy(
    AuthService::class,
    [
        'className' => AuthService::class,
        'constructorParams' => [
            [
                'email' => static fn() => new EmailAuthProvider(),
            ]
        ]
    ]
);