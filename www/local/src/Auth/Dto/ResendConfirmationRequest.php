<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Helper\Http\Mapper\RequestMapper;
use Bitrix\Main\Validation\Rule\Email;

final readonly class ResendConfirmationRequest
{
    use RequestMapper;

    public function __construct(
        #[Email()]
        public string $email,
    ) {
    }
}