<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Helper\Http\Mapper\Key;
use App\Helper\Http\Mapper\RequestMapper;
use Bitrix\Main\Validation\Rule\Email;
use Bitrix\Main\Validation\Rule\Length;

final readonly class LoginRequest
{
    use RequestMapper;

    public function __construct(
        #[Email()]
        public string $email,

        #[Length(min: 8)]
        public string $password,

        public bool $remember = true,

        #[Key('captcha_word')]
        public string $captchaWord = '',

        #[Key('captcha_sid')]
        public string $captchaSid = '',
    ) {
    }
}