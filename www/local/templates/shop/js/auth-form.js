/**
 * Форма авторизации на signa.
 *
 * Настройки компонента — константы ниже:
 *   FORM_WIDTH             — ширина формы (число px или CSS-значение)
 *   REQUEST_DELAY          — задержка перед запросом в мс, чтобы статусы было видно
 *   SUCCESS_REDIRECT_DELAY — пауза перед редиректом после успешного входа
 *
 * API:
 *   POST /api/auth/login (email, password, remember, captcha_word, captcha_sid)
 *     -> { status, data, errors: [{ code, message }] }
 *   GET /api/captcha -> { status, data: { captcha_sid, captcha_url } }
 *   Код ошибки поля — имя поля или «поле.правило»; трейс Bitrix начинается с «[Класс]».
 *
 * Пустые/некорректные поля отсекает нативная валидация браузера (required, type, minlength).
 * Ошибки валидации с сервера выводятся под своим полем, текст под полем имеет
 * зарезервированную высоту, поэтому инпуты не прыгают.
 * Ошибка авторизации (неверный логин/пароль) выводится текстом под заголовком,
 * все инпуты подсвечиваются красным.
 * Капча показывается только если сервер ответил CAPTCHA_REQUIRED.
 */

// Повторная регистрация тега в signa бросает ошибку, а Bitrix может подключить скрипт дважды.
signa.component('auth-form', ({ $this, signal, computed, html }, { useFetch }) => {
    const FORM_WIDTH = '380px';
    const REQUEST_DELAY = 350;
    const SUCCESS_REDIRECT_DELAY = 400;

    const FIELD_KEYS = ['email', 'password', 'captcha_word'];
    const CODE_CAPTCHA_REQUIRED = 'CAPTCHA_REQUIRED';
    const AUTH_ERROR_CODES = ['AUTH_ERROR', CODE_CAPTCHA_REQUIRED];

    /** Отладочный трейс Bitrix приходит как «[Класс] …» — в форме его не показываем. */
    const isTrace = (message) => /^\s*\[[^\]]+\]/.test(String(message || ''));

    const backurl = $this.dataset.backurl || '';
    const initialError = $this.dataset.error || '';

    // Ядро Bitrix кладёт в LAST_LOGIN логин, а он может быть не e-mail — подставляем только e-mail.
    const storedLogin = $this.dataset.login || '';
    const lastLogin = storedLogin.includes('@') ? storedLogin : '';

    $this.style.setProperty('--auth-form-width', FORM_WIDTH);

    // Уникальный префикс id/for: на странице может быть несколько форм (попап и страница).
    const uid = `af${Math.random().toString(36).slice(2, 10)}`;
    const fieldId = (name) => `${uid}-${name}`;

    // Начальная ошибка пришла из PHP после редиректа — сразу показываем её как ошибку входа.
    const email = signal(lastLogin);
    const password = signal('');
    const captchaWord = signal('');
    const remember = signal(true);

    const loginRequest = useFetch('/api/auth/login', { method: 'POST' });
    const captchaRequest = useFetch('/api/captcha', { cache: 'no-store' });

    const status = signal(initialError ? 'error' : '');
    const generalError = signal(initialError);
    const fieldErrors = signal({});
    const allFieldsInvalid = signal(initialError !== '');
    const captcha = captchaRequest.data;

    const loadCaptcha = async () => {
        const { ok, data } = await captchaRequest.execute();

        if (!ok || !data) {
            console.warn('[auth-form] не удалось получить капчу');
            return;
        }

        captchaWord.value = '';
    };

    /**
     * Раскладывает ошибки API по полям: код поля -> под инпут, первая прочая -> общий текст.
     * Коды валидации Bitrix — имя поля или «поле.правило».
     */
    const readErrors = (errors) => {
        const result = { fields: {}, general: '', generalCode: '' };

        for (const item of errors) {
            if (!item || !item.message || isTrace(item.message)) {
                continue;
            }

            const code = String(item.code || '');
            const field = code.split('.')[0];

            if (FIELD_KEYS.includes(field)) {
                if (!result.fields[field]) {
                    result.fields[field] = item.message;
                }
            } else if (!result.general) {
                result.general = item.message;
                result.generalCode = code;
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

    // Состояние капчи отдаём отдельному веб-компоненту <captcha-field>.
    const captchaSid = computed(() => captcha.value?.captcha_sid || '');
    const captchaUrl = computed(() => captcha.value?.captcha_url || '');
    const captchaError = computed(() => fieldErrors.value.captcha_word || '');
    const captchaInvalid = computed(
        () => Boolean(fieldErrors.value.captcha_word || allFieldsInvalid.value)
    );

    const handleCaptchaInput = (value) => {
        captchaWord.value = value;
        clearFieldError('captcha_word');
    };

    const toggleRemember = (event) => {
        remember.value = event.target.checked;
    };

    const buildBody = () => {
        const body = {
            email: email.value.trim(),
            password: password.value,
            remember: remember.value ? '1' : '0',
        };

        if (captcha.value) {
            body.captcha_word = captchaWord.value.trim();
            body.captcha_sid = captcha.value.captcha_sid || '';
        }

        return body;
    };

    const applyErrors = async ({ fields, general, generalCode }) => {
        const hasFieldErrors = Object.keys(fields).length > 0;

        fieldErrors.value = fields;
        generalError.value = general || (hasFieldErrors ? '' : 'Не удалось выполнить вход');
        allFieldsInvalid.value = AUTH_ERROR_CODES.includes(generalCode);

        if (generalCode === CODE_CAPTCHA_REQUIRED || captcha.value) {
            await loadCaptcha();
        }
    };

    const redirectAfterSuccess = () => {
        status.value = 'success';

        setTimeout(() => {
            if (backurl) {
                window.location.href = backurl;
            } else {
                window.location.reload();
            }
        }, SUCCESS_REDIRECT_DELAY);
    };

    const submit = async (event) => {
        event.preventDefault();

        if (status.value === 'pending') {
            return;
        }

        status.value = 'pending';
        generalError.value = '';
        fieldErrors.value = {};
        allFieldsInvalid.value = false;

        if (REQUEST_DELAY > 0) {
            await new Promise((resolve) => setTimeout(resolve, REQUEST_DELAY));
        }

        const { ok, errors } = await loginRequest.execute(buildBody());

        if (ok) {
            redirectAfterSuccess();
            return;
        }

        await applyErrors(readErrors(errors));
        status.value = 'error';
    };

    const renderStatus = () => {
        switch (status.value) {
            case 'pending': return html`<span class="auth-form__status auth-form__status--pending">Авторизую…</span>`;
            case 'error': return html`<span class="auth-form__status auth-form__status--error">Ошибка</span>`;
            case 'success': return html`<span class="auth-form__status auth-form__status--success">Успех</span>`;
            default: return '';
        }
    };

    const isInvalid = (name) => Boolean(fieldErrors.value[name] || allFieldsInvalid.value);
    const inputClass = (name) => `form-control${isInvalid(name) ? ' is-invalid' : ''}`;
    const ariaInvalid = (name) => (isInvalid(name) ? 'true' : 'false');

    const fieldError = (name) => html`
            <div class="auth-form__field-error">${fieldErrors.value[name] || ''}</div>
        `;

    const renderCaptcha = () => html`
            <captcha-field
                .sid=${captchaSid}
                .url=${captchaUrl}
                .error=${captchaError}
                .invalid=${captchaInvalid}
                .onInput=${handleCaptchaInput}
                .onRefresh=${loadCaptcha}
            ></captcha-field>
        `;

    return () => html`
            <form class="auth-form" onsubmit=${submit}>
                <h2 class="h4 mb-2">Авторизация</h2>

                <div class="auth-form__status-row" aria-live="polite">${renderStatus()}</div>
                <div class="auth-form__general" aria-live="polite">${generalError.value}</div>

                <div class="auth-form__field">
                    <label class="form-label" for=${fieldId('email')}>E-mail</label>
                    <input
                        id=${fieldId('email')}
                        class=${inputClass('email')}
                        type="email"
                        name="email"
                        autocomplete="username"
                        required
                        aria-invalid=${ariaInvalid('email')}
                        value=${email.value}
                        oninput=${fieldInput('email', email)}
                    >
                    ${fieldError('email')}
                </div>

                <div class="auth-form__field">
                    <label class="form-label" for=${fieldId('password')}>Пароль</label>
                    <input
                        id=${fieldId('password')}
                        class=${inputClass('password')}
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        minlength="8"
                        required
                        aria-invalid=${ariaInvalid('password')}
                        value=${password.value}
                        oninput=${fieldInput('password', password)}
                    >
                    ${fieldError('password')}
                </div>

                ${renderCaptcha()}

                <label class="form-check d-inline-flex align-items-center gap-2 mb-3">
                    <input
                        class="form-check-input m-0"
                        type="checkbox"
                        checked=${remember.value}
                        onchange=${toggleRemember}
                    >
                    <span class="form-check-label">Запомнить меня</span>
                </label>

                <button class="btn btn-primary w-100" type="submit" disabled=${status.value === 'pending'}>
                    Войти
                </button>
            </form>
        `;
});
