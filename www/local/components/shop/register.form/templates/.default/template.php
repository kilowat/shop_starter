<?php
/**
 * Шаблон формы регистрации: разметку рисует PHP, поведение — js/register-form.js.
 *
 * Ошибки сервера раскладывает register-form.js: текст — в [data-error-for],
 * подсветка — is-invalid на контролах.
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
>
    <form class="ui-form">
        <h2 class="h4 mb-2">Регистрация</h2>

        <div class="ui-form__field">
            <label class="form-label">
                Город
                <select class="form-select" name="city">
                    <option value="" disabled selected>Выберите город</option>
                    <option value="msk">Москва</option>
                </select>
            </label>
            <div class="ui-form__field-error" data-error-for="city" aria-live="polite"></div>
        </div>

        <div class="ui-form__field">
            <label class="form-label">
                Имя
                <input
                    class="form-control"
                    type="text"
                    name="name"
                    autocomplete="name"
                    minlength="2"
                    maxlength="50"
                    required
                >
            </label>
            <div class="ui-form__field-error" data-error-for="name" aria-live="polite"></div>
        </div>

        <div class="ui-form__field">
            <label class="form-label">
                E-mail
                <input
                    class="form-control"
                    type="email"
                    name="email"
                    autocomplete="email"
                    required
                >
            </label>
            <div class="ui-form__field-error" data-error-for="email" aria-live="polite"></div>
        </div>

        <div class="ui-form__field">
            <label class="form-label">
                Телефон
                <input
                    class="form-control"
                    type="tel"
                    name="phone"
                    autocomplete="tel"
                >
            </label>
            <div class="ui-form__field-error" data-error-for="phone" aria-live="polite"></div>
        </div>

        <div class="ui-form__field">
            <label class="form-label">
                Пароль
                <input
                    class="form-control"
                    type="password"
                    name="password"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >
            </label>
            <div class="ui-form__field-error" data-error-for="password" aria-live="polite"></div>
        </div>

        <div class="ui-form__field">
            <label class="form-label">
                Повторите пароль
                <input
                    class="form-control"
                    type="password"
                    name="confirm_password"
                    autocomplete="new-password"
                    minlength="8"
                    required
                >
            </label>
            <div class="ui-form__field-error" data-error-for="confirm_password" aria-live="polite"></div>
        </div>

        <button class="btn btn-primary w-100 mt-3" type="submit">Зарегистрироваться</button>

        <div class="text-center mt-3">
            <a href="/auth/?login=yes">Уже есть аккаунт? Войти</a>
        </div>
    </form>
</register-form>