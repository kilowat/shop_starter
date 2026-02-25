<?php
namespace Wigital\Lib\Auth\Provider;

use Exception;
use Wigital\Lib\Auth\Provider\AuthProvider;


final class EmailAuthProvider implements AuthProvider
{
    public function type(): string
    {
        return 'email';
    }

    public function authenticate(Credential $credential): void
    {
        $user = new \CUser();

        $result = $user->Login(
            $credential->login,
            $credential->password,
            'Y'
        );

        if ($result !== true) {
            throw new Exception(
                $result['MESSAGE'] ?? 'Authentication failed'
            );
        }
    }
}