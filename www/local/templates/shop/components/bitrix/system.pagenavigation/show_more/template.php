<?php
/**
 * Постраничная навигация «Показать ещё».
 *
 * Вместо номеров страниц — кнопка. JS (js/pagination/pagination.js) по клику
 * запрашивает следующую страницу, вынимает из ответа элементы списка и
 * дописывает их в текущий контейнер. Без JS кнопка работает как обычная
 * ссылка на следующую страницу.
 *
 * Тексты можно менять здесь, ниже. Прокидывать их через параметры компонента
 * не нужно.
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

$labelMore = 'Показать ещё';
$labelLoading = 'Загрузка…';
$labelDone = 'Показано всё';
$labelError = 'Не удалось загрузить. Попробуйте ещё раз.';

$totalItems = (int)($arResult['NavRecordCount'] ?? 0);
$shownItems = (int)($arResult['NavLastRecordShow'] ?? 0);
?>
<div
    class="pagination-more mt-4 text-center"
    data-pagination
    data-mode="show_more"
    data-page="<?= (int)$config['page'] ?>"
    data-total-pages="<?= (int)$config['total'] ?>"
    data-next-url="<?= $e($config['nextUrl']) ?>"
    data-page-param="<?= $e($config['pageParam']) ?>"
    data-total-items="<?= $totalItems ?>"
    data-shown-items="<?= $shownItems ?>"
    data-label-more="<?= $e($labelMore) ?>"
    data-label-loading="<?= $e($labelLoading) ?>"
    data-label-done="<?= $e($labelDone) ?>"
    data-label-error="<?= $e($labelError) ?>"
>
    <?php if ($config['nextUrl'] !== ''): ?>
        <a
            class="btn btn-outline-primary px-4"
            href="<?= $e($config['nextUrl']) ?>"
            data-pagination-more
            role="button"
        >
            <span
                class="spinner-border spinner-border-sm me-2 d-none"
                data-pagination-spinner
                aria-hidden="true"
            ></span>
            <span data-pagination-label><?= $e($labelMore) ?></span>
        </a>
    <?php endif; ?>

    <div class="pagination-more__status small text-muted mt-2" data-pagination-status aria-live="polite">
        Показано <?= $shownItems ?> из <?= $totalItems ?>
    </div>

    <?php if ($config['nextUrl'] !== ''): ?>
        <noscript>
            <p class="mt-2 mb-0">
                <a href="<?= $e($config['nextUrl']) ?>"><?= $e($labelMore) ?></a>
            </p>
        </noscript>
    <?php endif; ?>
</div>
