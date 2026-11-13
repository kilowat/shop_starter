<?php
/**
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<div class="d-flex align-items-center gap-2 mb-4">
    <? if ($arResult['SHOW_TITLE'] !== false): ?>
        <label class="small text-muted mb-0" for="catalog-sort"><?= $arResult['TITLE'] ?></label>
    <? endif; ?>

    <select id="catalog-sort" class="form-select form-select-sm w-auto" name="<?= $arResult['PARAM_NAME'] ?>">
        <? foreach ($arResult['OPTIONS'] as $option): ?>
            <option value="<?= $option['code'] ?>" <?= $option['code'] === $arResult['CURRENT'] ? 'selected' : '' ?>>
                <?= $option['name'] ?>
            </option>
        <? endforeach; ?>
    </select>
</div>