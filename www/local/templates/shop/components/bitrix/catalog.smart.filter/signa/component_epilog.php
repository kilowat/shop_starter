<?php
/**
 * Ассеты умного фильтра: ползунок noUiSlider нужен только этому шаблону.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

addAssetCss([
    'lib/nouislider/nouislider.min.css',
    'css/smart-filter.css',
]);

addAssetJs([
    'lib/nouislider/nouislider.min.js',
    'js/smart-filter.js',
]);