<?php
/**
 * Умный фильтр: верстку и поведение целиком рисует signa-компонент
 * <smart-filter> (js/app/smart-filter.js).
 *
 * Шаблон используется и страницей, и ajax-эндпоинтом /api/catalog/filter,
 * поэтому ассеты подключаются на странице, а не здесь.
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// Режим данных для ajax-эндпоинта /api/catalog/filter: из arResult берём
// только счётчики по значениям и общее число товаров — стейту больше не нужно
if (($arParams['RETURN_DATA'] ?? 'N') === 'Y') {
    $counts = [];

    foreach ($arResult['SIGNA_DATA']['ITEMS'] ?? [] as $id => $item) {
        foreach ($item['VALUES'] ?? [] as $key => $value) {
            $counts[$id]['VALUES'][$key] = [
                'CONTROL_NAME' => $value['CONTROL_NAME'] ?? '',
                'ELEMENT_COUNT' => (int)($value['ELEMENT_COUNT'] ?? 0),
                'DISABLED' => !empty($value['DISABLED']),
            ];
        }
    }

    echo \Bitrix\Main\Web\Json::encode([
        'ITEMS' => $counts,
        'TOTAL' => (int)($arResult['SIGNA_DATA']['TOTAL'] ?? 0),
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);

    return;
}

$signaData = \Bitrix\Main\Web\Json::encode(
    $arResult['SIGNA_DATA'] ?? [],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
);
?>
<script>window.__SMART_FILTER__ = <?= $signaData ?>;</script>
<smart-filter data-counts-url="/api/catalog/filter"></smart-filter>
