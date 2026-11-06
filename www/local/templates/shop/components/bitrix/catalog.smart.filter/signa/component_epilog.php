<?php


if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// Ассеты подключаем на странице: шаблоны фильтра и списка используются
// ещё и в ajax-эндпоинтах, где подключать CSS/JS не нужно
addAssetCss([
    'js/libs/nouislider/nouislider.min.css',
]);

addAssetJs([
    'js/libs/nouislider/nouislider.min.js',
    'js/signa/dist/signa.min.js',
    'js/app/catalog.js',
    'js/app/catalog-sort.js',
    'js/app/catalog-selected.js',
    'js/app/smart-filter.js',
]);