<?php

namespace App\Catalog;

/**
 * Сортировки каталога: короткий код в URL -> поля сортировки компонента.
 *
 * Код попадает в URL как ?sort=price_asc, поэтому список один и тот же
 * для сервера и для JS (см. Sort::options()).
 */
final class Sort
{
    public const DEFAULT_CODE = 'default';

    private const MAP = [
        'default' => [
            'name' => 'По популярности',
            'field' => 'sort',
            'order' => 'asc',
        ],
        'new' => [
            'name' => 'Сначала новые',
            'field' => 'id',
            'order' => 'desc',
        ],
        'price_asc' => [
            'name' => 'Цена: по возрастанию',
            'field' => 'CATALOG_PRICE_1',
            'order' => 'asc',
        ],
        'price_desc' => [
            'name' => 'Цена: по убыванию',
            'field' => 'CATALOG_PRICE_1',
            'order' => 'desc',
        ],
        'name_asc' => [
            'name' => 'Название: А-Я',
            'field' => 'name',
            'order' => 'asc',
        ],
        'name_desc' => [
            'name' => 'Название: Я-А',
            'field' => 'name',
            'order' => 'desc',
        ],
        'old' => [
            'name' => 'Сначала старые',
            'field' => 'id',
            'order' => 'asc',
        ],
    ];

    /**
     * Существует ли такой код сортировки.
     */
    public static function exists(?string $code): bool
    {
        return $code !== null && isset(self::MAP[$code]);
    }

    /**
     * Параметры сортировки для bitrix:catalog.section.
     *
     * @return array{code: string, field: string, order: string, field2: string, order2: string}
     */
    public static function resolve(?string $code): array
    {
        $code = self::exists($code) ? $code : self::DEFAULT_CODE;
        $sort = self::MAP[$code];

        return [
            'code' => $code,
            'field' => $sort['field'],
            'order' => $sort['order'],
            'field2' => 'id',
            'order2' => 'desc',
        ];
    }

    /**
     * Список сортировок для интерфейса.
     *
     * @return array<int, array{code: string, name: string}>
     */
    public static function options(): array
    {
        $result = [];

        foreach (self::MAP as $code => $sort) {
            $result[] = [
                'code' => $code,
                'name' => $sort['name'],
            ];
        }

        return $result;
    }
}
