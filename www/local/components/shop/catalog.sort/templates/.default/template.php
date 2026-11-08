<?php
/**
 * Шаблон компонента сортировки: обычный <select>, выбранное значение
 * приходит с сервера (arResult['CURRENT']).
 *
 * JS (js/catalog/sort.js) только пишет выбор в URL и возвращает значение
 * селекта при навигации назад/вперёд — разметку он не рисует.
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$h = static fn($value): string => htmlspecialcharsbx((string)$value);

$options = is_array($arResult['OPTIONS'] ?? null) ? $arResult['OPTIONS'] : [];
$param = (string)($arResult['PARAM_NAME'] ?? 'sort');
$current = (string)($arResult['CURRENT'] ?? '');
$default = (string)($arResult['DEFAULT'] ?? '');
$title = (string)($arResult['TITLE'] ?? 'Сортировка');
$showTitle = ($arResult['SHOW_TITLE'] ?? true) !== false;
?>
<div class="d-flex align-items-center gap-2">
    <?php if ($showTitle): ?>
        <label class="small text-muted mb-0" for="catalog-sort"><?= $h($title) ?></label>
    <?php endif; ?>

    <select id="catalog-sort"
            class="form-select form-select-sm w-auto"
            name="<?= $h($param) ?>"
            data-sort
            data-param="<?= $h($param) ?>"
            data-default="<?= $h($default) ?>">
        <?php foreach ($options as $option): ?>
            <option value="<?= $h($option['code'] ?? '') ?>"<?= (string)($option['code'] ?? '') === $current ? ' selected' : '' ?>>
                <?= $h($option['name'] ?? '') ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
