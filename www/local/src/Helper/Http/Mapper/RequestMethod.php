<?php

declare(strict_types=1);

namespace App\Helper\Http\Mapper;

enum RequestMethod: string
{
    case Get = 'GET';
    case Post = 'POST';
    case Put = 'PUT';
    case Patch = 'PATCH';
    case Delete = 'DELETE';

    /**
     * Нормализация списка методов (enum или строки) в массив строк.
     *
     * @param array<self|string> $methods
     * @return string[]
     */
    public static function normalize(array $methods): array
    {
        return array_map(
            static fn(self|string $m): string => $m instanceof self ? $m->value : strtoupper($m),
            $methods
        );
    }
}
