<?php
namespace Wigital\Lib\Auth\Services;

use Wigital\Lib\Auth\Provider\AuthProvider;
use Wigital\Lib\Auth\Provider\Credential;

class AuthService
{
    /**
     * @param array<string, AuthProvider> $providers
     */
    public function __construct(
        private array $providers
    ) {
    }

    public function login(Credential $credential, string $type = 'email'): void
    {
        if (!isset($this->providers[$type])) {
            throw new \DomainException("Unsupported login type: {$type}");
        }

        $this->providers[$type]->authenticate($credential);
    }
}
