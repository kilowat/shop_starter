/**
 * Форма регистрации на signa.
 *
 * Настройки компонента — константы ниже:
 *   FORM_WIDTH             — ширина формы (CSS-значение)
 *   REQUEST_DELAY          — задержка перед запросом в мс, чтобы статусы было видно
 *   SUCCESS_REDIRECT_DELAY — пауза перед редиректом после успешной регистрации
 *
 * API:
 *   POST /api/auth/register (name, email, phone, password, confirm_password)
 *     -> { status, data: { id, need_confirm }, errors: [{ code, message }] }
 *
 * Телефон необязателен и отправляется только если заполнен.
 * Пустые/некорректные поля отсекает нативная валидация браузера (required, type, minlength).
 * Совпадение паролей проверяется на клиенте, ошибка выводится под повтором пароля.
 * Ошибки API раскладываются по полям (код — имя поля или «поле.правило»),
 * первая прочая выводится под заголовком (например, «email уже существует»).
 * Если настройки сайта требуют подтверждения email, форма сообщает об отправке кода.
 */

signa.component('register-form', ({ $this, signal, html }, { useFetch }) => {
    const FORM_WIDTH = '380px';
    const REQUEST_DELAY = 350;
    const SUCCESS_REDIRECT_DELAY = 400;

    const FIELD_KEYS = ['name', 'email', 'phone', 'password', 'confirm_password'];
    const AUTH_URL = '/auth/?login=yes';

    /** Отладочный трейс Bitrix приходит как «[Класс] …» — в форме его не показываем. */
    const isTrace = (message) => /^\s*\[[^\]]+\]/.test(String(message || ''));

    /** Коды свойств DTO приходят в camelCase (confirmPassword) — приводим к ключам формы. */
    const toFieldKey = (code) => String(code || '')
        .split('.')[0]
        .replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);

    const backurl = $this.dataset.backurl || '/';

    $this.style.setProperty('--auth-form-width', FORM_WIDTH);

    // Уникальный префикс id/for: на странице может быть несколько форм.
    const uid = `rf${Math.random().toString(36).slice(2, 10)}`;
    const fieldId = (name) => `${uid}-${name}`;

    const name = signal('');
    const email = signal('');
    const phone = signal('');
    const password = signal('');
    const confirmPassword = signal('');

    const registerRequest = useFetch('/api/auth/register', { method: 'POST' });

    const status = signal('');
    const generalError = signal('');
    const successMessage = signal('');
    const fieldErrors = signal({});

    /**
     * Раскладывает ошибки API по полям: код поля -> под инпут, первая прочая -> общий текст.
     */
    const readErrors = (errors) => {
        const result = { fields: {}, general: '' };

        for (const item of errors) {
            if (!item || !item.message || isTrace(item.message)) {
                continue;
            }

            const field = toFieldKey(item.code);

            if (FIELD_KEYS.includes(field)) {
                if (!result.fields[field]) {
                    result.fields[field] = item.message;
                }
            } else if (!result.general) {
                result.general = item.message;
            }
        }

        return result;
    };

    const clearFieldError = (name) => {
        if (!fieldErrors.value[name]) {
            return;
        }

        const next = { ...fieldErrors.value };
        delete next[name];
        fieldErrors.value = next;
    };

    const fieldInput = (name, state) => (event) => {
        state.value = event.target.value;
        clearFieldError(name);
    };

    const buildBody = () => {
        const body = {
            name: name.value.trim(),
            email: email.value.trim(),
            password: password.value,
            confirm_password: confirmPassword.value,
        };

        const trimmedPhone = phone.value.trim();

        if (trimmedPhone !== '') {
            body.phone = trimmedPhone;
        }

        return body;
    };

    const redirectAfterSuccess = () => {
        status.value = 'success';

        setTimeout(() => {
            window.location.href = backurl;
        }, SUCCESS_REDIRECT_DELAY);
    };

    const submit = async (event) => {
        event.preventDefault();

        if (status.value === 'pending') {
            return;
        }

        status.value = 'pending';
        generalError.value = '';
        successMessage.value = '';
        fieldErrors.value = {};

        if (password.value !== confirmPassword.value) {
            fieldErrors.value = { confirm_password: 'Пароли не совпадают' };
            status.value = 'error';
            return;
        }

        if (REQUEST_DELAY > 0) {
            await new Promise((resolve) => setTimeout(resolve, REQUEST_DELAY));
        }

        const { ok, data, errors } = await registerRequest.execute(buildBody());

        if (ok) {
            if (data?.need_confirm) {
                status.value = 'success';
                successMessage.value = `Код подтверждения отправлен на ${email.value.trim()}.`;
                return;
            }

            redirectAfterSuccess();
            return;
        }

        const { fields, general } = readErrors(errors);
        const hasFieldErrors = Object.keys(fields).length > 0;

        fieldErrors.value = fields;
        generalError.value = general || (hasFieldErrors ? '' : 'Не удалось зарегистрироваться');
        status.value = 'error';
    };

    const renderStatus = () => {
        switch (status.value) {
            case 'pending': return html`<span class="auth-form__status auth-form__status--pending">Регистрирую…</span>`;
            case 'error': return html`<span class="auth-form__status auth-form__status--error">Ошибка</span>`;
            case 'success': return html`<span class="auth-form__status auth-form__status--success">Успех</span>`;
            default: return '';
        }
    };

    const isInvalid = (name) => Boolean(fieldErrors.value[name]);
    const inputClass = (name) => `form-control${isInvalid(name) ? ' is-invalid' : ''}`;
    const ariaInvalid = (name) => (isInvalid(name) ? 'true' : 'false');

    const fieldError = (name) => html`
            <div class="auth-form__field-error">${fieldErrors.value[name] || ''}</div>
        `;

    return () => html`
            <form class="auth-form" onsubmit=${submit}>
                <h2 class="h4 mb-2">Регистрация</h2>

                <div class="auth-form__status-row" aria-live="polite">${renderStatus()}</div>
                <div class=${successMessage.value ? 'auth-form__general text-success' : 'auth-form__general'} aria-live="polite">
                    ${successMessage.value || generalError.value}
                </div>

                <div class="auth-form__field">
                    <label class="form-label" for=${fieldId('name')}>Имя</label>
                    <input
                        id=${fieldId('name')}
                        class=${inputClass('name')}
                        type="text"
                        name="name"
                        autocomplete="name"
                        minlength="2"
                        maxlength="50"
                        required
                        aria-invalid=${ariaInvalid('name')}
                        value=${name.value}
                        oninput=${fieldInput('name', name)}
                    >
                    ${fieldError('name')}
                </div>

                <div class="auth-form__field">
                    <label class="form-label" for=${fieldId('email')}>E-mail</label>
                    <input
                        id=${fieldId('email')}
                        class=${inputClass('email')}
                        type="email"
                        name="email"
                        autocomplete="email"
                        required
                        aria-invalid=${ariaInvalid('email')}
                        value=${email.value}
                        oninput=${fieldInput('email', email)}
                    >
                    ${fieldError('email')}
                </div>

                <div class="auth-form__field">
                    <label class="form-label" for=${fieldId('phone')}>Телефон</label>
                    <input
                        id=${fieldId('phone')}
                        class=${inputClass('phone')}
                        type="tel"
                        name="phone"
                        autocomplete="tel"
                        aria-invalid=${ariaInvalid('phone')}
                        value=${phone.value}
                        oninput=${fieldInput('phone', phone)}
                    >
                    ${fieldError('phone')}
                </div>

                <div class="auth-form__field">
                    <label class="form-label" for=${fieldId('password')}>Пароль</label>
                    <input
                        id=${fieldId('password')}
                        class=${inputClass('password')}
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        minlength="8"
                        required
                        aria-invalid=${ariaInvalid('password')}
                        value=${password.value}
                        oninput=${fieldInput('password', password)}
                    >
                    ${fieldError('password')}
                </div>

                <div class="auth-form__field">
                    <label class="form-label" for=${fieldId('confirm_password')}>Повторите пароль</label>
                    <input
                        id=${fieldId('confirm_password')}
                        class=${inputClass('confirm_password')}
                        type="password"
                        name="confirm_password"
                        autocomplete="new-password"
                        minlength="8"
                        required
                        aria-invalid=${ariaInvalid('confirm_password')}
                        value=${confirmPassword.value}
                        oninput=${fieldInput('confirm_password', confirmPassword)}
                    >
                    ${fieldError('confirm_password')}
                </div>

                <button class="btn btn-primary w-100" type="submit" disabled=${status.value === 'pending'}>
                    Зарегистрироваться
                </button>

                <div class="text-center mt-3">
                    <a href=${AUTH_URL}>Уже есть аккаунт? Войти</a>
                </div>
            </form>
        `;
});