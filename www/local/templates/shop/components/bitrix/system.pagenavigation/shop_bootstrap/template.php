<?php
/**
 * Постраничная навигация (bitrix:system.pagenavigation) на Bootstrap 5.
 *
 * Выводится из bitrix:catalog.section (параметр PAGER_TEMPLATE) как NAV_STRING.
 * Поддерживает обычную и обратную (bDescPageNumbering) нумерацию,
 * показ всех элементов (SHOWALL_*) и сохранение GET-параметров фильтра/сортировки.
 *
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$this->setFrameMode(true);

if (
    !$arResult['NavShowAlways']
    && ($arResult['NavRecordCount'] == 0 || ($arResult['NavPageCount'] == 1 && $arResult['NavShowAll'] == false))
) {
    return;
}

$navNum = (int) $arResult['NavNum'];
$total = (int) $arResult['NavPageCount'];
$current = (int) $arResult['NavPageNomer'];
$start = (int) $arResult['nStartPage'];
$end = (int) $arResult['nEndPage'];
$desc = $arResult['bDescPageNumbering'] === true;
$savePage = $arResult['bSavePage'];

$urlPath = $arResult['sUrlPath'];
$query = (string) $arResult['NavQueryString'];
$querySuffix = $query !== '' ? '?' . $query : '';

/** Ссылка на страницу PAGEN_<num>; первая страница без bSavePage — корень раздела. */
$pageUrl = static function (int $page) use ($urlPath, $query, $querySuffix, $navNum, $savePage): string {
    if ($page === 1 && !$savePage) {
        return $urlPath . $querySuffix;
    }

    return $urlPath . '?' . ($query !== '' ? $query . '&amp;' : '') . 'PAGEN_' . $navNum . '=' . $page;
};

/** Номер страницы: текущая — active, остальные — ссылка. */
$pageItem = static function (int $page, int $label) use ($current, $pageUrl): string {
    if ($page === $current) {
        return '<li class="page-item active" aria-current="page"><span class="page-link">' . $label . '</span></li>';
    }

    return '<li class="page-item"><a class="page-link" href="' . $pageUrl($page) . '">' . $label . '</a></li>';
};

$dots = '<li class="page-item disabled"><span class="page-link" aria-hidden="true">…</span></li>';

$prevItem = static function (int $page) use ($pageUrl): string {
    return '<li class="page-item"><a class="page-link" href="' . $pageUrl($page) . '" rel="prev"'
        . ' aria-label="Предыдущая страница"><span aria-hidden="true">&laquo;</span></a></li>';
};

$nextItem = static function (int $page) use ($pageUrl): string {
    return '<li class="page-item"><a class="page-link" href="' . $pageUrl($page) . '" rel="next"'
        . ' aria-label="Следующая страница"><span aria-hidden="true">&raquo;</span></a></li>';
};

$prevDisabled = '<li class="page-item disabled"><span class="page-link" aria-hidden="true">&laquo;</span></li>';
$nextDisabled = '<li class="page-item disabled"><span class="page-link" aria-hidden="true">&raquo;</span></li>';

$html = '';

if ($desc) {
    // Обратная нумерация: «назад» ведёт к странице с большим PAGEN.
    $html .= $current < $total ? $prevItem($current + 1) : $prevDisabled;

    if ($current < $total && $start < $total) {
        $html .= $pageItem($total, 1);
        if ($start < $total - 1) {
            $html .= $dots;
        }
    }

    for ($page = $start; $page >= $end; $page--) {
        $html .= $pageItem($page, $total - $page + 1);
    }

    if ($current > 1 && $end > 1) {
        if ($end > 2) {
            $html .= $dots;
        }
        $html .= $pageItem(1, $total);
    }

    $html .= $current > 1 ? $nextItem($current - 1) : $nextDisabled;
} else {
    $html .= $current > 1 ? $prevItem($current - 1) : $prevDisabled;

    if ($start > 1) {
        $html .= $pageItem(1, 1);
        if ($start > 2) {
            $html .= $dots;
        }
    }

    for ($page = $start; $page <= $end; $page++) {
        $html .= $pageItem($page, $page);
    }

    if ($end < $total) {
        if ($end < $total - 1) {
            $html .= $dots;
        }
        $html .= $pageItem($total, $total);
    }

    $html .= $current < $total ? $nextItem($current + 1) : $nextDisabled;
}

if ($arResult['bShowAll']) {
    $allUrl = $urlPath . '?' . ($query !== '' ? $query . '&amp;' : '')
        . 'SHOWALL_' . $navNum . '=' . ($arResult['NavShowAll'] ? '0' : '1');

    $html .= '<li class="page-item"><a class="page-link" href="' . $allUrl . '">'
        . ($arResult['NavShowAll'] ? 'По страницам' : 'Все') . '</a></li>';
}

?>
<nav aria-label="Постраничная навигация">
    <ul class="pagination justify-content-center flex-wrap mb-0">
        <?= $html ?>
    </ul>
</nav>
