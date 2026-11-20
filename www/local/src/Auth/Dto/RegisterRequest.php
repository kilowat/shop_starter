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
        #[Length(min: 2, max: 50)]
        #[Sanitize(Filter::StripTags, Filter::Trim)]
        public string $name,

        #[Email()]
        public string $email,

        /**
         * Телефон необязателен. Без #[Phone()]: ядерное правило не пропускает null,
         * непустой телефон проверяется в AuthService::register().
         */
        #[Sanitize(Filter::Trim)]
        public ?string $phone,

        #[Length(min: 8)]
        public string $password,

        #[Key('confirm_password')]
        public string $confirmPassword,
    ) {
    }
}