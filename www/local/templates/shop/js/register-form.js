/**
 * Форма регистрации на signa.
 *
 * Общая логика (статусы, ошибки, submit) — в useForm; здесь — поля,
 * проверка совпадения паролей, подтверждение e-mail и редирект.
 *
 * API:
 *   POST /api/auth/register (name, email, phone, password, confirm_password)
 *     -> { status, data: { id, need_confirm }, errors: [{ code, message }] }
 *
 * Телефон необязателен и отправляется только если заполнен.
 * Пустые/некорректные поля отсекает нативная валидация браузера (required, type, minlength).
 * Совпадение паролей проверяется на клиенте, ошибка выводится под повтором пароля.
 * Ошибки API раскладываются по полям, первая прочая выводится под заголовком
 * (например, «email уже существует»).
 * Если настройки сайта требуют подтверждения email, форма сообщает об отправке кода.
 */

// Asset дедуплицирует пути, поэтому скрипт подключается один раз;
// повторная регистрация тега в signa бросила бы ошибку.
signa.component('register-form', ({ $this, signal, html }, { useForm }) => {
    const FIELD_KEYS = ['name', 'email', 'phone', 'password', 'confirm_password'];
    const AUTH_URL = '/auth/?login=yes';
    const REDIRECT_DELAY = 600;

    const backurl = $this.dataset.backurl || '/';

    const name = signal('');
    const email = signal('');
    const phone = signal('');
    const password = signal('');
    const confirmPassword = signal('');

    const form = useForm('/api/auth/register', {
        root: $this,
        fields: FIELD_KEYS,
        texts: {
            error: 'Не удалось зарегистрироваться',
            success: (data) => (
                data?.need_confirm
                    ? `Код подтверждения отправлен на ${email.value.trim()}.`
                    : 'Регистрация прошла успешно'
            ),
        },
        body: () => {
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
        },
        validate: (body) => (
            body.password !== body.confirm_password
                ? { confirm_password: 'Пароли не совпадают' }
                : null
        ),
        onSubmit: (data) => {
            if (data?.need_confirm) {
                return;
            }

            setTimeout(() => {
                window.location.href = backurl;
            }, REDIRECT_DELAY);
        },
    });

    const {
        status,
        fieldId,
        fieldInput,
        inputClass,
        ariaInvalid,
        fieldError,
        submit,
    } = form;

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

    return () => html`
        <form class="auth-form" onsubmit=${submit}>
            <h2 class="h4 mb-2">Регистрация</h2>

            ${renderField('name', 'Имя', name, {
                autocomplete: 'name', minlength: 2, maxlength: 50, required: true,
            })}
            ${renderField('email', 'E-mail', email, {
                type: 'email', autocomplete: 'email', required: true,
            })}
            ${renderField('phone', 'Телефон', phone, {
                type: 'tel', autocomplete: 'tel',
            })}
            ${renderField('password', 'Пароль', password, {
                type: 'password', autocomplete: 'new-password', minlength: 8, required: true,
            })}
            ${renderField('confirm_password', 'Повторите пароль', confirmPassword, {
                type: 'password', autocomplete: 'new-password', minlength: 8, required: true,
            })}

            <button class="btn btn-primary w-100 mt-3" type="submit" disabled=${status.value === 'pending'}>
                Зарегистрироваться
            </button>

            <div class="text-center mt-3">
                <a href=${AUTH_URL}>Уже есть аккаунт? Войти</a>
            </div>
        </form>
    `;
});
