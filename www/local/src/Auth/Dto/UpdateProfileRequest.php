<?php

declare(strict_types=1);

namespace App\Auth\Dto;

use App\Helper\Http\Mapper\Field;
use App\Helper\Http\Mapper\Filter;
use App\Helper\Http\Mapper\RequestMapper;
use App\Helper\Http\Mapper\Sanitize;

final readonly class UpdateProfileRequest
{
    use RequestMapper;

    public function __construct(
        #[Sanitize(Filter::StripTags, Filter::Trim)]
        public ?string $name,

        public ?string $phone,
    ) {
    }
}