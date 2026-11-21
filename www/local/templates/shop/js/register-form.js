/**
 * Форма регистрации: отправка, ошибки полей и возврат на backurl.
 * Разметку рисует PHP-шаблон компонента shop:register.form.
 *
 * POST /api/auth/register (name, email, phone, password, confirm_password)
 *   -> { id, need_confirm }
 */
signa.component('register-form', ({ $this }, { useFetch, notice }) => {
    const form = $this.querySelector('form');
    const submitButton = form.querySelector('[type="submit"]');
    const backurl = $this.dataset.backurl || '/';
    const request = useFetch('/api/auth/register', { method: 'POST' });

    const showFieldError = (name, message = '') => {
        const errorBox = form.querySelector(`[data-error-for="${name}"]`);
        const control = form.elements.namedItem(name);

        if (errorBox) {
            errorBox.textContent = message;
        }

        if (control) {
            control.classList.toggle('is-invalid', Boolean(message));
            control.setAttribute('aria-invalid', message ? 'true' : 'false');
        }
    };

    const resetErrors = () => {
        form.querySelectorAll('[data-error-for]').forEach((box) => {
            box.textContent = '';
        });
        form.querySelectorAll('[name]').forEach((control) => {
            control.classList.remove('is-invalid');
            control.setAttribute('aria-invalid', 'false');
        });
    };

    form.addEventListener('input', (event) => {
        if (event.target.name) {
            showFieldError(event.target.name);
        }
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (submitButton.disabled) {
            return;
        }

        resetErrors();

        const body = Object.fromEntries(new FormData(form));

        body.name = body.name.trim();
        body.email = body.email.trim();
        body.phone = (body.phone || '').trim();

        if (!body.phone) {
            delete body.phone;
        }

        if (body.password !== body.confirm_password) {
            showFieldError('confirm_password', 'Пароли не совпадают');
            return;
        }

        submitButton.disabled = true;

        const { ok, data, errors } = await request.execute(body);

        submitButton.disabled = false;

        if (ok) {
            notice.success(
                data?.need_confirm
                    ? `Код подтверждения отправлен на ${body.email}`
                    : 'Регистрация прошла успешно'
            );

            if (!data?.need_confirm) {
                setTimeout(() => {
                    window.location.href = backurl;
                }, 600);
            }

            return;
        }

        let generalError = '';
        let hasFieldErrors = false;

        for (const { code, message } of errors) {
            if (!message || String(message).startsWith('[')) {
                continue;
            }

            const field = code === 'confirmPassword' ? 'confirm_password' : code;

            if (form.elements.namedItem(field)) {
                showFieldError(field, message);
                hasFieldErrors = true;
            } else if (!generalError) {
                generalError = message;
            }
        }

        if (generalError) {
            notice.error(generalError);
        } else if (!hasFieldErrors) {
            notice.error('Не удалось зарегистрироваться');
        }
    });
});