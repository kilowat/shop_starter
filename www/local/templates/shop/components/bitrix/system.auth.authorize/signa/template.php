<?php
/**
 * Шаблон стандартной авторизации (bitrix:system.auth.authorize) для signa.
 *
 * Ядро подключает его через CMain::AuthForm(), когда страница требует
 * авторизации (NEED_AUTH или ограничение прав доступа), если в настройках
 * главного модуля задан шаблон компонентов авторизации «signa».
 *
 * @var array $arParams
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$message = '';

if (is_array($arParams['~AUTH_RESULT'] ?? null)) {
    $message = (string) ($arParams['~AUTH_RESULT']['MESSAGE'] ?? '');
} elseif (is_string($arParams['~AUTH_RESULT'] ?? null)) {
    $message = $arParams['~AUTH_RESULT'];
}

$message = trim(strip_tags(str_replace(['<br>', '<br />'], "\n", $message)));
?>
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php $GLOBALS['APPLICATION']->IncludeComponent(
                    'shop:auth.form',
                    '',
                    [
                        'BACKURL' => $arResult['~BACKURL'] ?? '',
                        'ERROR_MESSAGE' => $message,
                        'LAST_LOGIN' => $arResult['~LAST_LOGIN'] ?? '',
                    ],
                    false
                ); ?>
            </div>
        </div>
    </div>
</div>