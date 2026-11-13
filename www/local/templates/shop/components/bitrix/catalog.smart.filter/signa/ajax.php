<?php
/**
 * AJAX-ответ смарт-фильтра (компонент сам включает этот шаблон при ajax=y).
 *
 * Отдаёт только счётчики значений: { "CONTROL_NAME": количество товаров }.
 * Вёрстка в ответ не попадает — JSON собирается из готового arResult.
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$counts = [];

foreach ($arResult['ITEMS'] as $item) {
    foreach ($item['VALUES'] ?? [] as $value) {
        if (!empty($value['CONTROL_NAME'])) {
            $counts[$value['CONTROL_NAME']] = (int)($value['ELEMENT_COUNT'] ?? 0);
        }
    }
}

header('Content-Type: application/json; charset=utf-8');

echo \Bitrix\Main\Web\Json::encode($counts, JSON_UNESCAPED_UNICODE);
