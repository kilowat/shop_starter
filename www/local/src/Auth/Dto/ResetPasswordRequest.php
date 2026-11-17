<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Helper\Http\Mapper\Key;
use App\Helper\Http\Mapper\RequestMapper;
use Bitrix\Main\Validation\Rule\Email;
use Bitrix\Main\Validation\Rule\Length;
use Bitrix\Main\Validation\Rule\NotEmpty;

final readonly class ResetPasswordRequest
{
    use RequestMapper;

    public function __construct(
        #[Email()]
        public string $email,

        #[NotEmpty(errorMessage: 'Укажите контрольное слово из письма')]
        public string $checkword,

        #[Length(min: 8)]
        public string $password,

        #[Key('password_confirm')]
        public ?string $passwordConfirm,
    ) {
    }
}