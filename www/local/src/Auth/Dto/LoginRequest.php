<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Helper\Http\Mapper\RequestMapper;
use Bitrix\Main\Validation\Rule\Email;
use Bitrix\Main\Validation\Rule\Min;



final readonly class LoginRequest
{
    use RequestMapper;

    public function __construct(
        #[Email()]
        public string $email,

        #[Min(8)]
        public string $password,

        public bool $remember = false,
    ) {
    }
}
