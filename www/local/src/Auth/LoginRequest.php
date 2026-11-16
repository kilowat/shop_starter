<?php

namespace App\Auth;

use App\Helper\Http\Mapper\RequestMethod;
use App\Helper\Http\Mapper\RequestMapper;
use App\Helper\Http\Validator\RequiredOn;

final readonly class LoginRequest
{
    use RequestMapper;

    public function __construct(
        #[RequiredOn([RequestMethod::Post])]
        public string $email = '',

        #[RequiredOn([RequestMethod::Post])]
        public string $password = '',

        public bool $remember = false,
    ) {
    }
}
