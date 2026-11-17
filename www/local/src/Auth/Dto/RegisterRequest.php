<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Helper\Http\Mapper\Key;
use App\Helper\Http\Mapper\RequestMapper;

use Bitrix\Main\Validation\Rule\Email;

use Bitrix\Main\Validation\Rule\RegExp;
final readonly class RegisterRequest
{
    use RequestMapper;

    public function __construct(
        #[Email()]
        public string $email = '',

        #[RegExp()]
        public string $password,

        #[Key('confirm_password')]
        public string $confirmPassword,
    ) {
    }
}
