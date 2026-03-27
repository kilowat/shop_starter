<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

/** @var array $arResult */



\Bitrix\Main\Page\Asset::getInstance()->addString(
    '<script>window.__SMART_FILTER__=' . \Bitrix\Main\Web\Json::encode([
        'ITEMS' => $arResult['ITEMS'],
        'PRICES' => $arResult['PRICES'],
        'FILTER_URL' => $arResult['FILTER_URL'],
    ]) . ';</script>'
);