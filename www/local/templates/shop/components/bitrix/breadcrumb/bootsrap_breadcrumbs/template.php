<?php
/**
 * Хлебные крошки на Bootstrap 5.
 *
 * Компонент bitrix:breadcrumb вызывает этот файл через CMain::GetNavChain()
 * с $bIncludeOnce = true, поэтому шаблон обязан ВЕРНУТЬ строку, а не вывести её.
 * ob_start() здесь использовать нельзя: шаблон уже выполняется внутри
 * буферизации вывода, и PHP 8.2 выбрасывает fatal error.
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (empty($arResult)) {
    return '';
}

$items = array_values($arResult);
$lastIndex = count($items) - 1;

$html = '<nav aria-label="Хлебные крошки" class="mb-3">'
    . '<ol class="breadcrumb mb-0" itemscope itemtype="https://schema.org/BreadcrumbList">';

foreach ($items as $index => $item) {
    $title = htmlspecialcharsex($item['TITLE'] ?? '');
    $link = (string) ($item['LINK'] ?? '');
    $isLast = $index === $lastIndex;

    $html .= '<li class="breadcrumb-item' . ($isLast ? ' active' : '') . '"'
        . ' itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"'
        . ($isLast ? ' aria-current="page"' : '') . '>';

    if (!$isLast && $link !== '') {
        $html .= '<a href="' . htmlspecialcharsbx($link) . '" itemprop="item">'
            . '<span itemprop="name">' . $title . '</span></a>';
    } else {
        $html .= '<span itemprop="name">' . $title . '</span>';
    }

    $html .= '<meta itemprop="position" content="' . ($index + 1) . '">';
    $html .= '</li>';
}

$html .= '</ol></nav>';

return $html;