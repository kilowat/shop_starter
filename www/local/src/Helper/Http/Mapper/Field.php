<?php

namespace App\Helper\Http\Mapper;

use App\Helper\Http\Mapper\Cast;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class Field
{
    public function __construct(
        public readonly string $name,        // поле Bitrix, через точку для вложенных: 'PROPERTY_VALUES.TAGS'
        public readonly ?Cast $cast = null,
    ) {
    }
}