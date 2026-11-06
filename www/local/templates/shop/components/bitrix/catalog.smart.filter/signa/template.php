<?php
/**
 * Умный фильтр: верстку и поведение целиком рисует signa-компонент
 * <smart-filter> (js/app/smart-filter.js).
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

addAssetCss([
    'js/libs/nouislider/nouislider.min.css',
]);

addAssetJs([
    'js/libs/nouislider/nouislider.min.js',
    'js/signa/dist/signa.min.js',
    'js/app/smart-filter.js',
]);

$signaData = \Bitrix\Main\Web\Json::encode(
    $arResult['SIGNA_DATA'] ?? [],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
);
?>
<script>window.__SMART_FILTER__ = <?= $signaData ?>;</script>
<smart-filter></smart-filter>
