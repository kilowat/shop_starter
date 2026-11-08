<?php
/**
 * Классическая постраничная навигация.
 *
 * Ссылки обычные: клик уводит на новую страницу с полной перезагрузкой.
 * Никакого AJAX и прокрутки — поведение «как в обычном каталоге».
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
$url = $config['url'];
?>
<nav class="pagination-wrap mt-4" aria-label="Навигация по страницам">
    <ul class="pagination justify-content-center flex-wrap gap-1 mb-0">
        <li class="page-item<?= $config['hasPrev'] ? '' : ' disabled' ?>">
            <?php if ($config['hasPrev']): ?>
                <a class="page-link" href="<?= $e($config['prevUrl']) ?>" rel="prev">Назад</a>
            <?php else: ?>
                <span class="page-link" aria-disabled="true">Назад</span>
            <?php endif; ?>
        </li>

        <?php foreach ($config['pages'] as $item): ?>
            <?php if ($item === null): ?>
                <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php elseif ($item['current']): ?>
                <li class="page-item active" aria-current="page">
                    <span class="page-link"><?= (int)$item['display'] ?></span>
                </li>
            <?php else: ?>
                <li class="page-item">
                    <a class="page-link" href="<?= $e($url($item['page'])) ?>">
                        <?= (int)$item['display'] ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>

        <li class="page-item<?= $config['hasNext'] ? '' : ' disabled' ?>">
            <?php if ($config['hasNext']): ?>
                <a class="page-link" href="<?= $e($config['nextUrl']) ?>" rel="next">Вперёд</a>
            <?php else: ?>
                <span class="page-link" aria-disabled="true">Вперёд</span>
            <?php endif; ?>
        </li>
    </ul>
</nav>
