<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Helper\Http\Mapper\Filter;
use App\Helper\Http\Mapper\RequestMapper;
use App\Helper\Http\Mapper\Sanitize;
use Bitrix\Main\Validation\Rule\Email;

final readonly class EmailRequest
{
    use RequestMapper;

    public function __construct(
        #[Email()]
        #[Sanitize(Filter::Trim)]
        public string $email,
    ) {
    }
}