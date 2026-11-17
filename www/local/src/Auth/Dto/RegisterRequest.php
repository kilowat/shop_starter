<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Helper\Http\Mapper\Filter;
use App\Helper\Http\Mapper\Key;
use App\Helper\Http\Mapper\RequestMapper;
use App\Helper\Http\Mapper\Sanitize;
use Bitrix\Main\Validation\Rule\Email;
use Bitrix\Main\Validation\Rule\Length;

final readonly class RegisterRequest
{
    use RequestMapper;

    public function __construct(
        #[Email()]
        public string $email,

        #[Length(min: 8)]
        #[Sanitize(Filter::Raw)]
        public string $password,

        #[Key('confirm_password')]
        #[Sanitize(Filter::Raw)]
        public string $confirmPassword,
    ) {
    }
}