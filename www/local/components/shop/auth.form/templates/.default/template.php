<?php
/**
 * Шаблон формы авторизации: разметку рисует PHP, поведение — js/auth-form.js.
 *
 * Ошибки сервера раскладывает auth-form.js: текст — в [data-error-for],
 * подсветка — is-invalid на контролах. Капча показывается после CAPTCHA_REQUIRED.
 *
 * @var array $arResult
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$h = static fn($value): string => htmlspecialcharsbx((string) $value);


?>
<auth-form data-backurl="<?= $h($arResult['BACKURL']) ?>" data-error="<?= $h($arResult['ERROR_MESSAGE']) ?>">
    <form class="ui-form">
        <h2 class="h4 mb-2">Авторизация</h2>

        <div class="ui-form__field">
            <label class="form-label">
                E-mail
                <input class="form-control" type="email" name="email" autocomplete="username" required value="">
            </label>
            <div class="ui-form__field-error" data-error-for="email" aria-live="polite"></div>
        </div>

        <div class="ui-form__field">
            <label class="form-label">
                Пароль
                <input class="form-control" type="password" name="password" autocomplete="current-password"
                    minlength="8" required>
            </label>
            <div class="ui-form__field-error" data-error-for="password" aria-live="polite"></div>
        </div>

        <div class="captcha-field" data-captcha hidden>
            <label class="form-label">
                Проверочное слово
                <span class="captcha-field__row">
                    <input class="form-control" type="text" name="captcha_word" autocomplete="off">
                    <img class="captcha-field__image captcha-field__image--refresh" data-captcha-image
                        alt="Проверочное слово" title="Обновить капчу">
                </span>
            </label>
            <div class="captcha-field__error" data-error-for="captcha_word" aria-live="polite"></div>
        </div>

        <div class="ui-form__field">
            <label class="form-check d-inline-flex align-items-center gap-2">
                <input class="form-check-input m-0" type="checkbox" name="remember" value="1" checked>
                <span class="form-check-label">Запомнить меня</span>
            </label>
            <div class="ui-form__field-error" data-error-for="remember" aria-live="polite"></div>
        </div>

        <button class="btn btn-primary w-100 mt-3" type="submit">Войти</button>

        <div class="text-center mt-3">
            <a href="/register/">Нет аккаунта? Зарегистрироваться</a>
        </div>
    </form>
</auth-form>