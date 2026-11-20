<?php
/**
 * Шаблон ссылки пользователя: иконка человечка + подпись.
 *
 * Для гостя ссылка обёрнута в popup-trigger: клик открывает попап
 * с формой авторизации <auth-form>.
 *
 * @var array $arResult
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$h = static fn($value): string => htmlspecialcharsbx((string) $value);
?>
<?php if ($arResult['AUTHORIZED']): ?>
    <a
        href="<?= $h($arResult['LINK']) ?>"
        class="nav-link d-inline-flex align-items-center gap-2"
        title="<?= $h($arResult['TEXT']) ?>"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
        </svg>
        <span><?= $h($arResult['TEXT']) ?></span>
    </a>
<?php else: ?>
    <popup-trigger data-component="auth-form" data-backurl="<?= $h($arResult['BACKURL']) ?>">
        <a
            href="<?= $h($arResult['LINK']) ?>"
            class="nav-link d-inline-flex align-items-center gap-2"
            title="<?= $h($arResult['TEXT']) ?>"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
            </svg>
            <span><?= $h($arResult['TEXT']) ?></span>
        </a>
    </popup-trigger>
<?php endif; ?>