<?php

namespace Wigital\Lib\Auth\Provider;

interface AuthProvider
{
    public function type(): string;

    public function authenticate(Credential $credential): void;
}