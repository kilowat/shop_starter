/**
 * Форма авторизации на signa.
 *
 * Общая логика (статусы, ошибки, submit, редирект) — в useForm; здесь — поля и капча.
 *
 * API:
 *   POST /api/auth/login (email, password, remember, captcha_word, captcha_sid)
 *     -> { status, data, errors: [{ code, message }] }
 *   GET /api/captcha -> { status, data: { captcha_sid, captcha_url } }
 *
 * Пустые/некорректные поля отсекает нативная валидация браузера (required, type, minlength).
 * Ошибки валидации с сервера выводятся под своим полем, текст под полем имеет
 * зарезервированную высоту, поэтому инпуты не прыгают.
 * Ошибка авторизации (неверный логин/пароль) выводится текстом под заголовком,
 * все инпуты подсвечиваются красным.
 * Капча показывается только если сервер ответил CAPTCHA_REQUIRED.
 */

// Asset дедуплицирует пути, поэтому скрипт подключается один раз;
// повторная регистрация тега в signa бросила бы ошибку.
signa.component('auth-form', ({ $this, signal, computed, html }, { useFetch, useForm }) => {
    const FIELD_KEYS = ['email', 'password', 'captcha_word'];
    const CODE_CAPTCHA_REQUIRED = 'CAPTCHA_REQUIRED';

    const backurl = $this.dataset.backurl || '';
    const initialError = $this.dataset.error || '';

    // Ядро Bitrix кладёт в LAST_LOGIN логин, а он может быть не e-mail — подставляем только e-mail.
    const storedLogin = $this.dataset.login || '';
    const lastLogin = storedLogin.includes('@') ? storedLogin : '';

    const email = signal(lastLogin);
    const password = signal('');
    const captchaWord = signal('');
    const remember = signal(true);

    const captchaRequest = useFetch('/api/captcha', { cache: 'no-store' });
    const captcha = captchaRequest.data;

    const loadCaptcha = async () => {
        const { ok, data } = await captchaRequest.execute();

        if (!ok || !data) {
            console.warn('[auth-form] не удалось получить капчу');
            return;
        }

        captchaWord.value = '';
    };

    const form = useForm('/api/auth/login', {
        root: $this,
        fields: FIELD_KEYS,
        backurl,
        initialError,
        texts: { pending: 'Авторизую…', error: 'Не удалось выполнить вход' },
        body: () => {
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
        },
        onErrors: async ({ generalCode }) => {
            if (generalCode === CODE_CAPTCHA_REQUIRED || captcha.value) {
                await loadCaptcha();
            }
        },
    });

    const {
        status,
        fieldId,
        fieldInput,
        clearFieldError,
        inputClass,
        ariaInvalid,
        fieldError,
        renderMessages,
        submit,
    } = form;

    // Состояние капчи отдаём отдельному веб-компоненту <captcha-field>.
    const captchaSid = computed(() => captcha.value?.captcha_sid || '');
    const captchaUrl = computed(() => captcha.value?.captcha_url || '');
    const captchaError = computed(() => form.fieldErrors.value.captcha_word || '');
    const captchaInvalid = computed(
        () => Boolean(form.fieldErrors.value.captcha_word || form.allFieldsInvalid.value)
    );

    const handleCaptchaInput = (value) => {
        captchaWord.value = value;
        clearFieldError('captcha_word');
    };

    const toggleRemember = (event) => {
        remember.value = event.target.checked;
    };

    // Необязательные атрибуты (autocomplete, minlength, …) не передаём — uhtml их удалит.
    const renderField = (name, label, state, attrs = {}) => html`
        <div class="auth-form__field">
            <label class="form-label" for=${fieldId(name)}>${label}</label>
            <input
                id=${fieldId(name)}
                class=${inputClass(name)}
                type=${attrs.type || 'text'}
                name=${name}
                autocomplete=${attrs.autocomplete}
                minlength=${attrs.minlength}
                maxlength=${attrs.maxlength}
                required=${attrs.required}
                aria-invalid=${ariaInvalid(name)}
                value=${state.value}
                oninput=${fieldInput(name, state)}
            >
            ${fieldError(name)}
        </div>
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

            ${renderMessages()}

            ${renderField('email', 'E-mail', email, {
                type: 'email', autocomplete: 'username', required: true,
            })}
            ${renderField('password', 'Пароль', password, {
                type: 'password', autocomplete: 'current-password', minlength: 8, required: true,
            })}

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

            <div class="text-center mt-3">
                <a href="/register/">Нет аккаунта? Зарегистрироваться</a>
            </div>
        </form>
    `;
});
