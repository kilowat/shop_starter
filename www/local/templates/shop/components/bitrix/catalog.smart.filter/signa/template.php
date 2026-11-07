<?php
/**
 * Умный фильтр: верстку и поведение целиком рисует signa-компонент
 * <smart-filter> (js/app/smart-filter.js).
 *
 * Шаблон используется и страницей, и AJAX-обработчиком каталога,
 * поэтому ассеты подключаются на странице, а не здесь.
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$signaData = \Bitrix\Main\Web\Json::encode(
    $arResult['SIGNA_DATA'] ?? [],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
);
?>
<script>window.__SMART_FILTER__ = <?= $signaData ?>;</script>
<smart-filter></smart-filter>
