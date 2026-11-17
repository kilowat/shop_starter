<?php

declare(strict_types=1);

namespace App\Helper\Http\Mapper;

use Attribute;

/**
 * Набор фильтров для строкового поля DTO. Применяются по порядку.
 * Без атрибута действует только Filter::Trim.
 * Filter::Raw отключает обработку поля.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Sanitize
{
    /** @var Filter[] */
    public readonly array $filters;

    public function __construct(Filter ...$filters)
    {
        $this->filters = $filters;
    }
}