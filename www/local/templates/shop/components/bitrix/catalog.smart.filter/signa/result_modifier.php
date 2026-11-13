<?php
/**
 * Данные умного фильтра для шаблона signa.
 *
 * Компонент отдаёт строки уже через htmlspecialcharsbx, а шаблон экранирует
 * их повторно — поэтому сущности раскодируем здесь один раз.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arResult */

$decode = static function ($value) use (&$decode) {
    if (!is_array($value)) {
        return is_string($value) ? htmlspecialcharsback($value) : $value;
    }

    return array_map($decode, $value);
};

$items = $arResult['ITEMS'] ?? [];

foreach ($items as $id => $item) {
    foreach ($item['VALUES'] ?? [] as $key => $value) {
        // Картинка значения (бренд, цвет): шаблону нужен готовый src.
        $items[$id]['VALUES'][$key]['PICT'] = (string)($value['FILE']['SRC'] ?? '');
        unset($items[$id]['VALUES'][$key]['FILE']);
    }
}

// Цену показываем первой, остальные свойства — в порядке компонента.
uasort($items, static fn(array $a, array $b): int => (int)empty($a['PRICE']) <=> (int)empty($b['PRICE']));

$arResult['ITEMS'] = $decode($items);
$arResult['HIDDEN'] = $decode($arResult['HIDDEN'] ?? []);
$arResult['FORM_ACTION'] = htmlspecialcharsback((string)($arResult['FORM_ACTION'] ?? ''));
