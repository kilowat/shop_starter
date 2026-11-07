<?php
/**
 * Шаблон компонента сортировки.
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$config = [
    'options' => $arResult['OPTIONS'],
    'current' => $arResult['CURRENT'],
    'param' => $arResult['PARAM_NAME'],
    'title' => $arResult['TITLE'],
    'showTitle' => (bool)$arResult['SHOW_TITLE'],
    'default' => $arResult['DEFAULT'],
];

$json = \Bitrix\Main\Web\Json::encode(
    $config,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
);
?>
<div class="catalog-sort">
    <script type="application/json" class="catalog-sort__config"><?= $json ?></script>
    <catalog-sort></catalog-sort>
</div>
