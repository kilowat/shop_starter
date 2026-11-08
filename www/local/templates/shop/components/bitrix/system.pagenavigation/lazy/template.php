<?php
/**
 * Полностью ленивая постраничная навигация.
 *
 * Визуально пагинации нет: после списка остаётся sentinel. JS
 * (js/pagination/pagination.js) следит за ним через IntersectionObserver
 * и сам подгружает следующую страницу при прокрутке вниз.
 *
 * Настройки ниже меняются прямо в этом файле:
 *   $showSpinner — показывать ли спиннер во время подгрузки;
 *   $rootMargin  — за сколько пикселей до конца списка начинать загрузку.
 *
 * Спиннер можно заменить своим markup: важно оставить
 * data-pagination-spinner, если хотите, чтобы он скрывался/показывался
 * автоматически по классу is-loading на корне.
 *
 * @var array $arResult
 * @var CBitrixComponentTemplate $this
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

require_once __DIR__ . '/../include/pagination_helper.php';

if (!ShopPaginationTemplate::shouldShow($arResult)) {
    return;
}

$config = ShopPaginationTemplate::prepare($arResult);
$e = [ShopPaginationTemplate::class, 'e'];

// ---------- настройки шаблона ----------
$showSpinner = true;
$rootMargin = '400px';

$labelMore = 'Показать ещё';
$labelLoading = 'Загрузка…';
$labelDone = 'Показано всё';
$labelError = 'Не удалось загрузить. Попробуйте ещё раз.';
// --------------------------------------

$totalItems = (int)($arResult['NavRecordCount'] ?? 0);
$shownItems = (int)($arResult['NavLastRecordShow'] ?? 0);
?>
<div
    class="pagination-lazy mt-4"
    data-pagination
    data-mode="lazy"
    data-page="<?= (int)$config['page'] ?>"
    data-total-pages="<?= (int)$config['total'] ?>"
    data-next-url="<?= $e($config['nextUrl']) ?>"
    data-page-param="<?= $e($config['pageParam']) ?>"
    data-root-margin="<?= $e($rootMargin) ?>"
    data-total-items="<?= $totalItems ?>"
    data-shown-items="<?= $shownItems ?>"
    data-label-more="<?= $e($labelMore) ?>"
    data-label-loading="<?= $e($labelLoading) ?>"
    data-label-done="<?= $e($labelDone) ?>"
    data-label-error="<?= $e($labelError) ?>"
>
    <div class="pagination-lazy__sentinel text-center" data-pagination-sentinel>
        <?php if ($showSpinner): ?>
            <div
                class="pagination-lazy__spinner spinner-border spinner-border-sm text-secondary"
                data-pagination-spinner
                role="status"
            >
                <span class="visually-hidden"><?= $e($labelLoading) ?></span>
            </div>
        <?php endif; ?>
    </div>

    <div class="visually-hidden" data-pagination-status aria-live="polite">
        Показано <?= $shownItems ?> из <?= $totalItems ?>
    </div>

    <?php if ($config['nextUrl'] !== ''): ?>
        <noscript>
            <p class="text-center mt-2 mb-0">
                <a href="<?= $e($config['nextUrl']) ?>"><?= $e($labelMore) ?></a>
            </p>
        </noscript>
    <?php endif; ?>
</div>
