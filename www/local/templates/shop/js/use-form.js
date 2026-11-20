/**
 * Общая логика форм на signa: статусы, ошибки полей, submit и редирект.
 *
 * const form = useForm('/api/auth/login', {
 *     root: $this,                      // элемент формы: задаёт --auth-form-width
 *     fields: ['email', 'password'],    // ключи полей для раскладки ошибок API
 *     body: () => ({ ... }),            // тело запроса, вызывается один раз за submit
 *     backurl: '',                      // пусто → reload после успеха
 *     initialError: '',                 // ошибка из PHP после редиректа
 *     texts: { pending: 'Отправляю…', error: 'Не удалось отправить форму' },
 *     validate: (body) => null,         // клиентская проверка: { поле: текст } или null
 *     onSuccess: (data, form) => false, // вернуть true → успех обработан вручную, без редиректа
 *     onErrors: async (parsed) => {},   // хук после раскладки ошибок, до статуса error (капча)
 *     ...fetchOptions                   // остальное уходит в useFetch: headers, cache и т.д.
 * });
 *
 * Ширина (width, по умолчанию 380px) — единственная необязательная настройка вида;
 * задержки статусов и коды ошибок авторизации зашиты константами ниже.
 *
 * Состояние — сигналы signa:
 *   status: '' | 'pending' | 'error' | 'success'
 *   generalError, successMessage, fieldErrors, allFieldsInvalid
 *
 * Хелперы для разметки: fieldId, inputClass, ariaInvalid, fieldError —
 * id/классы/ошибка поля; fieldInput(name, state, read?) — oninput/onchange,
 * read позволяет взять event.target.checked для checkbox; setFieldValue —
 * записать значение вручную (radio/custom); clearFieldError — сбросить ошибку.
 * Саму разметку поля рисует форма: input, textarea, select, radio и т.п.
 * Например:
 *   html`<div class="auth-form__field">
 *       <label class="form-label" for=${fieldId('about')}>О себе</label>
 *       <textarea
 *           id=${fieldId('about')}
 *           class=${inputClass('about')}
 *           aria-invalid=${ariaInvalid('about')}
 *           .value=${about.value}
 *           oninput=${fieldInput('about', about)}
 *       ></textarea>
 *       ${fieldError('about')}
 *   </div>`
 *
 * renderStatus / renderMessages — общие блоки статуса и сообщений;
 * submit — обработчик onsubmit.
 * request — useFetch-инстанс запроса формы (например, refresh() после сбоя сети).
 *
 * Контракт ответа API тот же, что у useFetch:
 *   { status: 'success'|'error', data, errors: [{ code, message }] }.
 * Код ошибки поля — имя поля или «поле.правило»; трейс Bitrix начинается с «[Класс]».
 */

signa.state('useForm', ({ signal, html }, { useFetch }) => {
    const REQUEST_DELAY = 350;   // задержка перед запросом, чтобы статусы было видно
    const REDIRECT_DELAY = 400;
    // Стандартные коды Bitrix: при них подсвечиваются все поля формы.
    const ALL_FIELDS_INVALID_CODES = ['AUTH_ERROR', 'CAPTCHA_REQUIRED'];

    /** Отладочный трейс Bitrix приходит как «[Класс] …» — в форме его не показываем. */
    const isTrace = (message) => /^\s*\[[^\]]+\]/.test(String(message || ''));

    /** Коды свойств DTO приходят в camelCase (confirmPassword) — приводим к ключам формы. */
    const toFieldKey = (code) => String(code || '')
        .split('.')[0]
        .replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);

    return (url, options = {}) => {
        const {
            root = null,
            width = '380px',
            fields: fieldKeys = [],
            body: buildBody,
            backurl = '',
            initialError = '',
            texts = {},
            validate = null,
            onSuccess = null,
            onErrors = null,
            ...fetchOptions
        } = options;

        const {
            pending: pendingText = 'Отправляю…',
            error: fallbackError = 'Не удалось отправить форму',
        } = texts;

        if (typeof buildBody !== 'function') {
            throw new Error('[useForm] body обязателен');
        }

        if (root) {
            root.style.setProperty('--auth-form-width', width);
        }

        const request = useFetch(url, { method: 'POST', ...fetchOptions });

        // Уникальный префикс id/for: на странице может быть несколько форм.
        const uid = `f${Math.random().toString(36).slice(2, 10)}`;
        const fieldId = (name) => `${uid}-${name}`;

        // Начальная ошибка пришла из PHP после редиректа — сразу показываем её.
        const status = signal(initialError ? 'error' : '');
        const generalError = signal(initialError);
        const successMessage = signal('');
        const fieldErrors = signal({});
        const allFieldsInvalid = signal(initialError !== '');

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
                const field = toFieldKey(code);

                if (fieldKeys.includes(field)) {
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

        const fieldInput = (name, state, read = (event) => event.target.value) => (event) => {
            state.value = read(event);
            clearFieldError(name);
        };

        const setFieldValue = (name, state) => (value) => {
            state.value = value;
            clearFieldError(name);
        };

        const redirectAfterSuccess = () => {
            status.value = 'success';

            setTimeout(() => {
                if (backurl) {
                    window.location.href = backurl;
                } else {
                    window.location.reload();
                }
            }, REDIRECT_DELAY);
        };

        /** Ошибки уже разложены по сигналам; onErrors ждём до переключения статуса. */
        const applyErrors = async ({ fields, general, generalCode }) => {
            const hasFieldErrors = Object.keys(fields).length > 0;

            fieldErrors.value = fields;
            generalError.value = general || (hasFieldErrors ? '' : fallbackError);
            allFieldsInvalid.value = ALL_FIELDS_INVALID_CODES.includes(generalCode);

            if (onErrors) {
                await onErrors({ fields, general, generalCode });
            }

            status.value = 'error';
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
            allFieldsInvalid.value = false;

            try {
                const body = buildBody();
                const validation = validate ? validate(body) : null;

                if (validation) {
                    if (typeof validation !== 'object') {
                        console.warn('[useForm] validate должен вернуть { поле: текст } или null');
                    } else if (Object.keys(validation).length > 0) {
                        fieldErrors.value = validation;
                        status.value = 'error';
                        return;
                    }
                }

                await new Promise((resolve) => setTimeout(resolve, REQUEST_DELAY));

                const { ok, data, errors } = await request.execute(body);

                if (ok) {
                    if (onSuccess && onSuccess(data, form) === true) {
                        return;
                    }

                    redirectAfterSuccess();
                    return;
                }

                await applyErrors(readErrors(errors));
            } catch (e) {
                // body или хук бросил — не оставляем форму в pending навсегда.
                status.value = 'error';
                generalError.value = fallbackError;
                console.error('[useForm]', e);
            }
        };

        const renderStatus = () => {
            switch (status.value) {
                case 'pending': return html`<span class="auth-form__status auth-form__status--pending">${pendingText}</span>`;
                case 'error': return html`<span class="auth-form__status auth-form__status--error">Ошибка</span>`;
                case 'success': return html`<span class="auth-form__status auth-form__status--success">Успех</span>`;
                default: return '';
            }
        };

        const isInvalid = (name) => Boolean(fieldErrors.value[name] || allFieldsInvalid.value);
        const inputClass = (name, base = 'form-control') => `${base}${isInvalid(name) ? ' is-invalid' : ''}`;
        const ariaInvalid = (name) => (isInvalid(name) ? 'true' : 'false');

        const fieldError = (name) => html`
            <div class="auth-form__field-error">${fieldErrors.value[name] || ''}</div>
        `;

        const renderMessages = () => html`
            <div class="auth-form__status-row" aria-live="polite">${renderStatus()}</div>
            <div class=${successMessage.value ? 'auth-form__general text-success' : 'auth-form__general'} aria-live="polite">
                ${successMessage.value || generalError.value}
            </div>
        `;

        const form = {
            request,
            status,
            generalError,
            successMessage,
            fieldErrors,
            allFieldsInvalid,
            fieldId,
            fieldInput,
            setFieldValue,
            clearFieldError,
            inputClass,
            ariaInvalid,
            fieldError,
            renderStatus,
            renderMessages,
            submit,
        };

        return form;
    };
});
