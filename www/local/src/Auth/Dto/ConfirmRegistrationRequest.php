<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Helper\Http\Mapper\RequestMapper;

use Bitrix\Main\Validation\Rule\Email;
use Bitrix\Main\Validation\Rule\NotEmpty;
final readonly class ConfirmRegistrationRequest
{
    use RequestMapper;

    public function __construct(
        #[Email()]
        public string $email,

        #[NotEmpty(errorMessage: 'Укажите поле код')]
        public string $code,

        public bool $remember = false,
    ) {
    }
}
