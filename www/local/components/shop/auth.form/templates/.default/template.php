<?php
/**
 * Шаблон формы авторизации: отдаёт данные сервера веб-компоненту <auth-form>.
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$h = static fn($value): string => htmlspecialcharsbx((string) $value);
?>
<auth-form
    data-backurl="<?= $h($arResult['BACKURL']) ?>"
    data-error="<?= $h($arResult['ERROR_MESSAGE']) ?>"
    data-login="<?= $h($arResult['LAST_LOGIN']) ?>"
></auth-form>