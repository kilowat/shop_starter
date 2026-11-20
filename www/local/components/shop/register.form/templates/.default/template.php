<?php
/**
 * Шаблон формы регистрации: отдаёт данные сервера веб-компоненту <register-form>.
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$h = static fn($value): string => htmlspecialcharsbx((string) $value);
?>
<register-form
    data-backurl="<?= $h($arResult['BACKURL']) ?>"
></register-form>