<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Helper\Http\Mapper\RequestMethod;
use App\Helper\Http\Mapper\RequestMapper;
use App\Helper\Http\Validator\RequiredOn;

final readonly class ConfirmRegistrationRequest
{
    use RequestMapper;

    public function __construct(
        #[RequiredOn([RequestMethod::Post])]
        public string $email = '',

        #[RequiredOn([RequestMethod::Post])]
        public string $code = '',

        public bool $remember = false,
    ) {
    }
}
