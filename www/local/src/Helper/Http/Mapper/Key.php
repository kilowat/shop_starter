<?php

namespace App\Helper\Http\Mapper;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Key
{
    public function __construct(
        public readonly string $key,
        public readonly bool $includeIfNull = true,
    ) {
    }
}