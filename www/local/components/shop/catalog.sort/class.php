<?php
/**
 * Компонент сортировки каталога.
 *
 * Параметры:
 *   FIELDS     — варианты сортировки, каждый: CODE, NAME, FIELD, ORDER,
 *                необязательно FIELD2, ORDER2
 *   DEFAULT    — CODE варианта по умолчанию (иначе первый)
 *   PARAM_NAME — имя параметра в запросе (по умолчанию sort)
 *   SORT_NAME  — имя глобальной переменной для catalog.section (по умолчанию arrSort)
 *   TITLE, SHOW_TITLE — подпись селекта
 *
 * Работает как смарт-фильтр: кладёт выбранную сортировку в $GLOBALS[SORT_NAME].
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Context;

class CatalogSortComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        $options = $this->getOptions();

        // Нет вариантов — ничего не делаем, catalog.section остаётся со своей сортировкой
        if ($options === []) {
            return null;
        }

        $default = $this->getDefaultCode($options);
        $current = $this->getCurrentCode($options, $default);

        $this->publishSort($options[$current]);

        $this->arResult = [
            'OPTIONS' => $this->getOptionsForTemplate($options),
            'CURRENT' => $current,
            'DEFAULT' => $default,
            'PARAM_NAME' => $this->getParamName(),
            'SORT_NAME' => $this->getSortName(),
            'TITLE' => $this->param('TITLE', 'Сортировка'),
            'SHOW_TITLE' => $this->param('SHOW_TITLE', 'Y') !== 'N',
        ];

        $this->includeComponentTemplate();

        return $this->arResult;
    }

    // ---------- Выбор варианта ----------

    private function getDefaultCode(array $options): string
    {
        $default = $this->param('DEFAULT');

        return isset($options[$default]) ? $default : (string) array_key_first($options);
    }

    private function getCurrentCode(array $options, string $default): string
    {
        // get() читает и GET, и POST, поэтому работает и в AJAX
        $requested = Context::getCurrent()->getRequest()->get($this->getParamName());
        $requested = is_string($requested) ? trim($requested) : '';

        return isset($options[$requested]) ? $requested : $default;
    }

    // ---------- Результат ----------

    /** Кладёт выбранную сортировку туда, откуда её читает catalog.section.php */
    private function publishSort(array $option): void
    {
        $GLOBALS[$this->getSortName()] = [
            'FIELD' => $option['FIELD'],
            'ORDER' => $option['ORDER'],
            'FIELD2' => $option['FIELD2'],
            'ORDER2' => $option['ORDER2'],
        ];
    }

    private function getOptionsForTemplate(array $options): array
    {
        $result = [];

        foreach ($options as $option) {
            $result[] = ['code' => $option['CODE'], 'name' => $option['NAME']];
        }

        return $result;
    }

    // ---------- Параметры ----------

    private function getParamName(): string
    {
        return $this->param('PARAM_NAME', 'sort');
    }

    private function getSortName(): string
    {
        $name = $this->param('SORT_NAME', 'arrSort');

        // Имя попадает в $GLOBALS, поэтому допускаем только корректный идентификатор
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) ? $name : 'arrSort';
    }

    /** Берёт «сырое» значение (~NAME), иначе обработанное; пустое заменяет на $default */
    private function param(string $name, string $default = ''): string
    {
        $value = $this->arParams['~' . $name] ?? $this->arParams[$name] ?? '';
        $value = trim((string) $value);

        return $value !== '' ? $value : $default;
    }

    // ---------- Разбор FIELDS ----------

    /** @return array<string, array> варианты, индексированные по CODE */
    private function getOptions(): array
    {
        $raw = $this->arParams['~FIELDS'] ?? $this->arParams['FIELDS'] ?? [];
        $options = [];

        foreach ((array) $raw as $item) {
            $option = $this->normalizeOption($item);

            if ($option !== null) {
                $options[$option['CODE']] = $option;
            }
        }

        return $options;
    }

    private function normalizeOption(mixed $item): ?array
    {
        if (!is_array($item)) {
            return null;
        }

        $code = trim((string) ($item['CODE'] ?? ''));
        $field = trim((string) ($item['FIELD'] ?? ''));

        if ($code === '' || $field === '') {
            return null;
        }

        return [
            'CODE' => $code,
            'NAME' => (string) ($item['NAME'] ?? $code),
            'FIELD' => $field,
            'ORDER' => $this->normalizeOrder($item['ORDER'] ?? '', 'asc'),
            'FIELD2' => trim((string) ($item['FIELD2'] ?? '')) ?: 'id',
            'ORDER2' => $this->normalizeOrder($item['ORDER2'] ?? '', 'desc'),
        ];
    }

    private function normalizeOrder(mixed $value, string $default): string
    {
        $order = strtolower(trim((string) $value));

        return in_array($order, ['asc', 'desc'], true) ? $order : $default;
    }
}