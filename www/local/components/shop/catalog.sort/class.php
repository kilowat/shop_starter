<?php
/**
 * Компонент сортировки каталога.
 *
 * На вход принимает FIELDS — массив (или serialized/JSON-строку) полей:
 *   CODE   — значение в URL
 *   NAME   — подпись
 *   FIELD  — поле сортировки bitrix:catalog.section
 *   ORDER  — asc|desc
 *   FIELD2 / ORDER2 — необязательная вторая сортировка
 *
 * На выходе arResult['SORT'] с готовыми field/order для catalog.section.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Context;

class ShopCatalogSortComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        $fields = $this->normalizeFields(
            $this->arParams['~FIELDS'] ?? $this->arParams['FIELDS'] ?? []
        );

        $param = trim((string)($this->arParams['~PARAM_NAME'] ?? $this->arParams['PARAM_NAME'] ?? ''));
        if ($param === '') {
            $param = 'sort';
        }

        $default = trim((string)($this->arParams['~DEFAULT'] ?? $this->arParams['DEFAULT'] ?? ''));
        if ($default === '' || !isset($fields[$default])) {
            $default = (string)array_key_first($fields);
        }

        $current = trim((string)($this->arParams['~CURRENT'] ?? $this->arParams['CURRENT'] ?? ''));
        if ($current === '') {
            $current = $this->requestValue($param);
        }

        if ($current === '' || !isset($fields[$current])) {
            $current = $default;
        }

        $sort = $fields[$current] ?? $fields[$default];

        $this->arResult = [
            'FIELDS' => $fields,
            'OPTIONS' => array_map(
                static fn(array $field): array => [
                    'code' => $field['CODE'],
                    'name' => $field['NAME'],
                ],
                array_values($fields)
            ),
            'CURRENT' => $current,
            'SORT' => [
                'FIELD' => $sort['FIELD'],
                'ORDER' => $sort['ORDER'],
                'FIELD2' => $sort['FIELD2'],
                'ORDER2' => $sort['ORDER2'],
            ],
            'PARAM_NAME' => $param,
            'TITLE' => (string)($this->arParams['~TITLE'] ?? $this->arParams['TITLE'] ?? 'Сортировка'),
            'SHOW_TITLE' => ($this->arParams['~SHOW_TITLE'] ?? $this->arParams['SHOW_TITLE'] ?? 'Y') !== 'N',
            'DEFAULT' => $default,
        ];

        $this->includeComponentTemplate();
    }

    private function requestValue(string $name): string
    {
        $value = Context::getCurrent()->getRequest()->getQuery($name);

        if (is_array($value)) {
            $value = reset($value);
        }

        return trim((string)$value);
    }

    /**
     * @param array|string $raw
     */
    private function normalizeFields($raw): array
    {
        if (is_string($raw)) {
            $raw = trim($raw);

            if ($raw !== '' && ($raw[0] === '[' || $raw[0] === '{')) {
                $decoded = json_decode($raw, true);

                if (is_array($decoded)) {
                    $raw = $decoded;
                }
            } elseif ($raw !== '') {
                $decoded = @unserialize($raw, ['allowed_classes' => false]);

                if (is_array($decoded)) {
                    $raw = $decoded;
                }
            }
        }

        if (!is_array($raw)) {
            $raw = [];
        }

        $fields = [];

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $code = trim((string)($item['CODE'] ?? ''));
            $field = trim((string)($item['FIELD'] ?? ''));

            if ($code === '' || $field === '') {
                continue;
            }

            $order = mb_strtolower(trim((string)($item['ORDER'] ?? 'asc')));
            $order2 = mb_strtolower(trim((string)($item['ORDER2'] ?? 'desc')));

            $fields[$code] = [
                'CODE' => $code,
                'NAME' => (string)($item['NAME'] ?? $code),
                'FIELD' => $field,
                'ORDER' => $order === 'desc' ? 'desc' : 'asc',
                'FIELD2' => (string)($item['FIELD2'] ?? 'id'),
                'ORDER2' => $order2 === 'asc' ? 'asc' : 'desc',
            ];
        }

        return $fields !== [] ? $fields : $this->defaultFields();
    }

    private function defaultFields(): array
    {
        return [
            'default' => [
                'CODE' => 'default',
                'NAME' => 'По популярности',
                'FIELD' => 'sort',
                'ORDER' => 'asc',
                'FIELD2' => 'id',
                'ORDER2' => 'desc',
            ],
        ];
    }
}
