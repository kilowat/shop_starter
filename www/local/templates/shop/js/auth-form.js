/**
 * Форма входа: отправка, ошибки полей, капча и возврат на backurl.
 * Разметку рисует PHP-шаблон компонента shop:auth.form.
 *
 * POST /api/auth/login (email, password, remember, captcha_word, captcha_sid)
 * GET  /api/captcha     -> { captcha_sid, captcha_url }
 */
signa.component('auth-form', ({ $this }, { useFetch, notice }) => {
    const form = $this.querySelector('form');
    const submitButton = form.querySelector('[type="submit"]');
    const backurl = $this.dataset.backurl || '';

    const captchaBox = $this.querySelector('[data-captcha]');
    const captchaInput = form.elements.captcha_word;
    const captchaImage = $this.querySelector('[data-captcha-image]');

    const login = useFetch('/api/auth/login', { method: 'POST' });
    const captchaRequest = useFetch('/api/captcha', { cache: 'no-store' });

    let captchaSid = '';

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

    const invalidateAll = () => {
        form.querySelectorAll('[name]').forEach((control) => {
            control.classList.add('is-invalid');
            control.setAttribute('aria-invalid', 'true');
        });
    };

    const loadCaptcha = async () => {
        const { ok, data } = await captchaRequest.execute();

        if (!ok) {
            return;
        }

        captchaSid = data.captcha_sid;
        captchaImage.src = data.captcha_url;
        captchaInput.value = '';
        captchaInput.required = true;
        captchaBox.hidden = false;
    };

    if ($this.dataset.error) {
        notice.error($this.dataset.error);
        invalidateAll();
    }

    captchaImage.addEventListener('click', loadCaptcha);

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
        submitButton.disabled = true;

        const body = {
            email: form.elements.email.value.trim(),
            password: form.elements.password.value,
            remember: form.elements.remember.checked ? '1' : '0',
        };

        if (!captchaBox.hidden) {
            body.captcha_word = captchaInput.value.trim();
            body.captcha_sid = captchaSid;
        }

        const { ok, errors } = await login.execute(body);

        if (ok) {
            notice.success('Авторизация успешна');

            setTimeout(() => {
                if (backurl) {
                    window.location.href = backurl;
                } else {
                    window.location.reload();
                }
            }, 600);

            return;
        }

        let generalError = '';
        let hasFieldErrors = false;
        let captchaRequired = false;
        let invalidate = false;

        for (const { code, message } of errors) {
            if (!message || String(message).startsWith('[')) {
                continue;
            }

            const field = code === 'captchaWord' ? 'captcha_word' : code;

            if (code === 'CAPTCHA_REQUIRED') {
                captchaRequired = true;
            }

            if (code === 'AUTH_ERROR' || code === 'CAPTCHA_REQUIRED') {
                invalidate = true;
            }

            if (form.elements.namedItem(field)) {
                showFieldError(field, message);
                hasFieldErrors = true;
            } else if (!generalError) {
                generalError = message;
            }
        }

        if (captchaRequired || !captchaBox.hidden) {
            await loadCaptcha();
        }

        if (invalidate) {
            invalidateAll();
        }

        if (generalError) {
            notice.error(generalError);
        } else if (!hasFieldErrors) {
            notice.error('Не удалось выполнить вход');
        }

        submitButton.disabled = false;
    });
});