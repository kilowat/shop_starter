<?php
/**
 * Шаблон компонента сортировки.
 *
 * Верстку рисует signa-компонент <catalog-sort> (js/catalog/catalog-sort.js).
 * Начальные данные кладём в signa-state SortState.
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$config = [
    'options' => is_array($arResult['OPTIONS'] ?? null) ? $arResult['OPTIONS'] : [],
    'param' => (string) $arResult['PARAM_NAME'],
    'default' => (string) $arResult['DEFAULT'],
    'title' => (string) $arResult['TITLE'],
    'showTitle' => (bool) $arResult['SHOW_TITLE'],
];

$json = \Bitrix\Main\Web\Json::encode(
    $config,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
);
?>
<script>
    window.signa.state('SortState').value = <?= $json ?>;
</script>
<catalog-sort class="catalog-sort"></catalog-sort>