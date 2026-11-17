<?php

declare(strict_types=1);

namespace App\Helper\Http\Mapper;

enum Filter
{
    case Trim;          // обрезать пробелы по краям (включая неразрывные и невидимые)
    case StripTags;     // убрать HTML-теги
    case SpecialChars;  // htmlspecialchars, нужен редко: экранировать лучше при выводе
    case Raw;           // не обрабатывать вообще (пароли и т.п.)

    public function apply(string $value): string
    {
        return match ($this) {
            self::Trim => preg_replace(
                '/^[\s\x{00A0}\x{200B}\x{FEFF}]+|[\s\x{00A0}\x{200B}\x{FEFF}]+$/u',
                '',
                $value
            ) ?? trim($value),
            self::StripTags => strip_tags($value),
            self::SpecialChars => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            self::Raw => $value,
        };
    }
}