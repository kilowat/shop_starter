<?php
/**
 * Шаблон меню шапки (bitrix:menu) на Bootstrap 5.
 *
 * @var array $arResult
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
?>
<ul class="navbar-nav ms-auto flex-row flex-wrap column-gap-2">
    <?php foreach ($arResult as $arItem): ?>
        <li class="nav-item">
            <a
                class="nav-link<?= $arItem['SELECTED'] ? ' active fw-semibold' : '' ?>"
                href="<?= $arItem['LINK'] ?>"
                <?= $arItem['SELECTED'] ? 'aria-current="page"' : '' ?>
            ><?= htmlspecialcharsbx($arItem['TEXT']) ?></a>
        </li>
    <?php endforeach; ?>
</ul>
