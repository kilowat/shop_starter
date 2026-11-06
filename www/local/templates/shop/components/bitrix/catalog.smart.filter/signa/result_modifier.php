<?php
/**
 * Подготовка данных умного фильтра для signa-компонента <smart-filter>.
 *
 * Компонент отдаёт строки уже через htmlspecialcharsbx, а uhtml экранирует
 * значения сам, поэтому сущности декодируем здесь.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */

$decode = static function ($value) use (&$decode) {
    if (is_array($value)) {
        $result = [];

        foreach ($value as $key => $item) {
            if ($key === 'FILE') {
                continue;
            }

            $result[$key] = $decode($item);
        }

        return $result;
    }

    return is_string($value) ? htmlspecialcharsback($value) : $value;
};

$items = [];

foreach ($arResult['ITEMS'] as $id => $item) {
    if (empty($item['VALUES']) && empty($item['PRICE'])) {
        continue;
    }

    $items[$id] = $decode($item);

    // картинку значения (бренды, цвета) отдаём готовым src
    foreach (array_keys($items[$id]['VALUES'] ?? []) as $valueKey) {
        $src = $item['VALUES'][$valueKey]['FILE']['SRC'] ?? '';

        if ($src !== '') {
            $items[$id]['VALUES'][$valueKey]['PICT'] = $src;
        }
    }
}

$arResult['SIGNA_DATA'] = [
    'ITEMS' => $items,
    'PRICES' => $arResult['PRICES'] ?? [],
    'FILTER_NAME' => $arResult['FILTER_NAME'] ?? '',
    'FORM_ACTION' => htmlspecialcharsback((string)($arResult['FORM_ACTION'] ?? '')),
    'FILTER_URL' => htmlspecialcharsback((string)($arResult['FILTER_URL'] ?? '')),
    'ELEMENT_COUNT' => $arResult['ELEMENT_COUNT'] ?? null,
    'HIDDEN' => $decode($arResult['HIDDEN'] ?? []),
];

// Общее число товаров по собранному фильтру ($GLOBALS[FILTER_NAME] уже заполнен компонентом).
// Считаем, только когда число реально нужно: списку оно не требуется
if (($arParams['SKIP_COUNT'] ?? 'N') !== 'Y') {
    $filter = $GLOBALS[$arResult['FILTER_NAME'] ?? ''] ?? [];

    if (is_array($filter)) {
        $filter = array_merge([
            'IBLOCK_ID' => (int)$arParams['IBLOCK_ID'],
            'ACTIVE' => 'Y',
            'ACTIVE_DATE' => 'Y',
            'CHECK_PERMISSIONS' => 'N',
            'INCLUDE_SUBSECTIONS' => 'Y',
        ], $filter);

        if (\Bitrix\Main\Loader::includeModule('catalog')) {
            $filter = \CProductQueryBuilder::convertOldFilter($filter);
        }
    }

    // Пустой GROUP BY переключает GetList в режим COUNT(DISTINCT BE.ID)
    $arResult['TOTAL_COUNT'] = is_array($filter)
        ? (int)\CIBlockElement::GetList([], $filter, [], false)
        : 0;
}

$arResult['SIGNA_DATA']['TOTAL'] = (int)($arResult['TOTAL_COUNT'] ?? 0);
