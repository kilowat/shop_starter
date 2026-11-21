/**
 * Капча для форм на signa: загрузка, ввод, ошибки и тело запроса.
 *
 * const form = useForm('/api/auth/login', {
 *     fields: ['email', 'captcha_word'],       // имя поля капчи — в fields
 *     body: () => ({ email, ...captcha.body() }),
 *     onErrors: (parsed) => captcha.onErrors(parsed),
 * });
 * const captcha = useCaptcha(form);
 *
 * Разметку капчи форма ставит сама: `${captcha.render()}`.
 * render() ничего не рисует, пока сервер не вернул данные капчи.
 *
 * Опции:
 *   url          — эндпоинт капчи (по умолчанию /api/captcha)
 *   field        — имя поля с введённым словом (captcha_word)
 *   requiredCode — код ошибки, после которого капча показывается (CAPTCHA_REQUIRED)
 *   request      — опции useFetch для запроса капчи
 *
 * Сигналы: word, sid, image, error, invalid.
 * Методы: load (обновить), input, body, onErrors, render.
 */

signa.state('useCaptcha', ({ signal, computed, html }, { useFetch }) => (form, options = {}) => {
    const {
        url = '/api/captcha',
        field = 'captcha_word',
        requiredCode = 'CAPTCHA_REQUIRED',
        request: requestOptions = { cache: 'no-store' },
    } = options;

    const request = useFetch(url, requestOptions);
    const data = request.data;
    const word = signal('');

    const load = async () => {
        const { ok } = await request.execute();

        if (!ok) {
            console.warn('[useCaptcha] не удалось получить капчу');
            return;
        }

        word.value = '';
    };

    const sid = computed(() => data.value?.captcha_sid || '');
    const image = computed(() => data.value?.captcha_url || '');
    const error = computed(() => form.fieldErrors.value[field] || '');
    const invalid = computed(
        () => Boolean(form.fieldErrors.value[field] || form.allFieldsInvalid.value)
    );

    const input = (value) => {
        word.value = value;
        form.clearFieldError(field);
    };

    const body = () => (
        data.value
            ? { [field]: word.value.trim(), captcha_sid: sid.value }
            : {}
    );

    const onErrors = async ({ generalCode }) => {
        if (generalCode === requiredCode || data.value) {
            await load();
        }
    };

    const render = () => html`
        <captcha-field
            .sid=${sid}
            .url=${image}
            .error=${error}
            .invalid=${invalid}
            .onInput=${input}
            .onRefresh=${load}
        ></captcha-field>
    `;

    return { request, data, word, sid, image, error, invalid, load, input, body, onErrors, render };
});