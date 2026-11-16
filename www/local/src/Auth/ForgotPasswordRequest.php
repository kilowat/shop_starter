<?php

namespace App\Auth;

use App\Helper\Http\Mapper\Key;
use App\Helper\Http\Mapper\RequestMethod;
use App\Helper\Http\Mapper\RequestMapper;
use App\Helper\Http\Validator\RequiredOn;

final readonly class ForgotPasswordRequest
{
    use RequestMapper;

    public function __construct(
        #[RequiredOn([RequestMethod::Post])]
        public string $email = '',

        #[Key('captcha_word')]
        public string $captchaWord = '',

        #[Key('captcha_sid')]
        public string|int $captchaSid = 0,
    ) {
    }
}
