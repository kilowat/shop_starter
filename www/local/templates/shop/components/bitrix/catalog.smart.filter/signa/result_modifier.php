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

/**
 * Считает товары по фильтру так же, как это делает bitrix:catalog.section:
 * условия по торговым предложениям (OFFERS) и каталожные поля (цены, наличие)
 * уходят в SubQuery по SKU.
 *
 * Если отдать OFFERS прямо в CIBlockElement::GetList, он молча проигнорирует
 * это условие: счётчик покажет больше товаров, чем список реально выведет.
 */
$countElements = static function (array $filter, int $iblockId): int {
    $filter = array_merge([
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y',
        'ACTIVE_DATE' => 'Y',
        'CHECK_PERMISSIONS' => 'N',
        'INCLUDE_SUBSECTIONS' => 'Y',
    ], $filter);

    if (!\Bitrix\Main\Loader::includeModule('catalog')) {
        return (int)\CIBlockElement::GetList([], $filter, [], false);
    }

    $filter = \CProductQueryBuilder::convertOldFilter($filter);

    $catalogFilter = [];

    foreach ($filter as $key => $value) {
        if (\CProductQueryBuilder::isCatalogFilterField($key)) {
            $catalogFilter[$key] = $value;
            unset($filter[$key]);
        }
    }

    $offers = is_array($filter['OFFERS'] ?? null) ? $filter['OFFERS'] : [];
    unset($filter['OFFERS']);

    $sku = \CCatalogSKU::GetInfoByProductIBlock($iblockId);

    if (empty($sku)) {
        return (int)\CIBlockElement::GetList([], array_merge($filter, $catalogFilter), [], false);
    }

    $subFilter = array_merge($offers, $catalogFilter, [
        'IBLOCK_ID' => $sku['IBLOCK_ID'],
        'ACTIVE' => 'Y',
        'ACTIVE_DATE' => 'Y',
    ]);

    $subQuery = \CIBlockElement::SubQuery('PROPERTY_' . $sku['SKU_PROPERTY_ID'], $subFilter);

    if ($offers) {
        $filter['=ID'] = $subQuery;
    } elseif ($catalogFilter) {
        // цена/наличие: подходит и сам товар, и его предложение
        $filter[] = ['LOGIC' => 'OR', [$catalogFilter], '=ID' => $subQuery];
    }

    // Пустой GROUP BY переключает GetList в режим COUNT(DISTINCT BE.ID)
    return (int)\CIBlockElement::GetList([], $filter, [], false);
};

// Общее число товаров по собранному фильтру ($GLOBALS[FILTER_NAME] уже заполнен компонентом).
// Считаем, только когда число реально нужно: списку оно не требуется
if (($arParams['SKIP_COUNT'] ?? 'N') !== 'Y') {
    $filter = $GLOBALS[$arResult['FILTER_NAME'] ?? ''] ?? [];

    $arResult['TOTAL_COUNT'] = is_array($filter)
        ? $countElements($filter, (int)$arParams['IBLOCK_ID'])
        : 0;
}

// Счётчики по значениям: { CONTROL_NAME: число товаров } — их отдаём контроллеру
$counts = [];

foreach ($items as $item) {
    foreach ($item['VALUES'] ?? [] as $value) {
        if (!empty($value['CONTROL_NAME'])) {
            $counts[$value['CONTROL_NAME']] = (int)($value['ELEMENT_COUNT'] ?? 0);
        }
    }
}

$arResult['COUNTS'] = $counts;
$arResult['TOTAL'] = (int)($arResult['TOTAL_COUNT'] ?? 0);
$arResult['SIGNA_DATA']['TOTAL'] = $arResult['TOTAL'];
