<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Helper\Http\Mapper\Field;
use App\Helper\Http\Mapper\Filter;
use App\Helper\Http\Mapper\RequestMapper;
use App\Helper\Http\Mapper\Sanitize;
use Bitrix\Main\Validation\Rule\Phone;

final readonly class ProfilePayload
{
    use RequestMapper;

    public function __construct(
        #[Field('NAME')]
        #[Sanitize(Filter::StripTags, Filter::Trim)]
        public ?string $name,

        #[Phone()]
        #[Field('PERSONAL_PHONE')]
        public ?string $phone,
    ) {
    }
}