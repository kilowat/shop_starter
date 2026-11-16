<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Helper\Http\Mapper\Key;
use App\Helper\Http\Mapper\RequestMethod;
use App\Helper\Http\Mapper\RequestMapper;
use App\Helper\Http\Validator\RequiredOn;

final readonly class RegisterRequest
{
    use RequestMapper;

    public function __construct(
        #[RequiredOn([RequestMethod::Post])]
        public string $email = '',

        public string $name = '',

        public string $phone = '',

        public bool $remember = false,

        #[Key('captcha_word')]
        public string $captchaWord = '',

        #[Key('captcha_sid')]
        public string|int $captchaSid = 0,
    ) {
    }
}
