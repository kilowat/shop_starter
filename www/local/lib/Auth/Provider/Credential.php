<?php

namespace Wigital\Lib\Auth\Provider;

final readonly class Credential
{
    public function __construct(
        public string $login,
        public ?string $password,
        public ?string $code,
        public ?string $remember,
    ) {
    }
}