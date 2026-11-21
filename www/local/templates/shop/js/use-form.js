/**
 * Общая логика форм на signa: статусы, ошибки полей и submit.
 *
 * const form = useForm('/api/auth/login', {
 *     root: $this,                      // элемент формы: задаёт --auth-form-width
 *     fields: ['email', 'password'],    // ключи полей для раскладки ошибок API
 *     body: () => ({ ... }),            // тело запроса, вызывается один раз за submit
 *     initialError: '',                 // ошибка из PHP после редиректа
 *     texts: { error: 'Не удалось отправить форму' },
 *     validate: (body) => null,         // клиентская проверка: { поле: текст } или null
 *     onSubmit: (data, form) => {},     // хук после успешного ответа; редирект — здесь
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
 * renderMessages — общий текст ошибки под заголовком;
 * submit — обработчик onsubmit.
 * request — useFetch-инстанс запроса формы (например, refresh() после сбоя сети).
 *
 * Контракт ответа API тот же, что у useFetch:
 *   { status: 'success'|'error', data, errors: [{ code, message }] }.
 * Код ошибки поля — имя поля или «поле.правило»; трейс Bitrix начинается с «[Класс]».
 */

signa.state('useForm', ({ signal, html }, { useFetch }) => {
    const REQUEST_DELAY = 350;   // задержка перед запросом, чтобы статусы было видно
    // Стандартные коды Bitrix: при них подсвечиваются все поля формы.
    const ALL_FIELDS_INVALID_CODES = ['AUTH_ERROR', 'CAPTCHA_REQUIRED'];
    const VALIDATION_MESSAGE = 'Поля заполнены некорректно';

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
            initialError = '',
            texts = {},
            validate = null,
            onSubmit = null,
            onErrors = null,
            ...fetchOptions
        } = options;

        const {
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

        /** Ошибки уже разложены по сигналам; onErrors ждём до переключения статуса. */
        const applyErrors = async ({ fields, general, generalCode }) => {
            const hasFieldErrors = Object.keys(fields).length > 0;

            fieldErrors.value = fields;
            generalError.value = general || (hasFieldErrors ? VALIDATION_MESSAGE : fallbackError);
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
                        generalError.value = VALIDATION_MESSAGE;
                        status.value = 'error';
                        return;
                    }
                }

                await new Promise((resolve) => setTimeout(resolve, REQUEST_DELAY));

                const { ok, data, errors } = await request.execute(body);

                if (ok) {
                    status.value = 'success';

                    if (onSubmit) {
                        await onSubmit(data, form);
                    }

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

        const isInvalid = (name) => Boolean(fieldErrors.value[name] || allFieldsInvalid.value);
        const inputClass = (name, base = 'form-control') => `${base}${isInvalid(name) ? ' is-invalid' : ''}`;
        const ariaInvalid = (name) => (isInvalid(name) ? 'true' : 'false');

        const fieldError = (name) => html`
            <div class="auth-form__field-error" aria-live="polite">${fieldErrors.value[name] || ''}</div>
        `;

        const renderMessages = () => {
            const message = successMessage.value || generalError.value;

            if (!message) {
                return '';
            }

            return html`
                <div class=${successMessage.value ? 'auth-form__general text-success' : 'auth-form__general'} aria-live="polite">
                    ${message}
                </div>
            `;
        };

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
            renderMessages,
            submit,
        };

        return form;
    };
});
