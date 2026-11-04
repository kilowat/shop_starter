<?php

namespace App\Helper\Http\Mapper;

enum Cast
{
    case YesNo;           // true -> 'Y', false -> 'N'
    case DateTimeString;  // строка в формате сайта
    case Items;           // список без пустых значений

    public function apply(mixed $value): mixed
    {
        return match ($this) {
            self::YesNo => $value ? 'Y' : 'N',
            self::DateTimeString => is_string($value) && ($ts = strtotime($value)) !== false
                ? \Bitrix\Main\Type\DateTime::createFromTimestamp($ts)->toString()
                : $value,
            self::Items => array_values(array_filter(
                (array) $value,
                static fn($v) => $v !== null && $v !== ''
            )),
        };
    }
}