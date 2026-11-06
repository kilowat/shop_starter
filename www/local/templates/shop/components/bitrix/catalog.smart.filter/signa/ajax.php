<?php
/**
 * Ответ на ajax-запрос фильтра (callback считает актуальные счётчики).
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

echo \Bitrix\Main\Web\Json::encode(
    ['result' => $arResult['SIGNA_DATA'] ?? []],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
);
