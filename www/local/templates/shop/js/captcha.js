/**
 * Капча — отдельный веб-компонент signa.
 *
 * Использование:
 *   <captcha-field
 *       data-sid="..."
 *       data-url="..."
 *       data-error="..."
 *       data-invalid="false"
 *   ></captcha-field>
 *
 * Свойства:
 *   sid/url       — данные капчи; без них компонент ничего не рисует
 *   error/invalid — текст ошибки и подсветка поля
 *   onInput(word) — введённое слово
 *   onRefresh()   — клик по картинке (если передан)
 */
signa.component('captcha-field', ({ html, signal, prop, effect }) => {
    const sid = prop('sid', String, '');
    const url = prop('url', String, '');
    const error = prop('error', String, '');
    const invalid = prop('invalid', Boolean, false);
    const onInput = prop('onInput');
    const onRefresh = prop('onRefresh');

    const word = signal('');
    const inputId = `captcha-${Math.random().toString(36).slice(2, 10)}`;

    // Новый sid — новая картинка, прежний ввод к ней уже не относится.
    let lastSid = sid.value;

    effect(() => {
        if (sid.value === lastSid) {
            return;
        }

        lastSid = sid.value;
        word.value = '';
    });

    const handleInput = (event) => {
        word.value = event.target.value;

        if (typeof onInput === 'function') {
            onInput(word.value);
        }
    };

    const handleRefresh = (event) => {
        if (typeof onRefresh !== 'function') {
            return;
        }

        event.preventDefault();
        onRefresh();
    };

    return () => {
        const imageUrl = url.value
            || (sid.value ? `/bitrix/tools/captcha.php?captcha_sid=${sid.value}` : '');

        if (!imageUrl) {
            return '';
        }

        const canRefresh = typeof onRefresh === 'function';

        return html`
            <div class="captcha-field">
                <label class="form-label" for=${inputId}>Проверочное слово</label>
                <div class="captcha-field__row">
                    <input
                        id=${inputId}
                        class=${invalid.value ? 'form-control is-invalid' : 'form-control'}
                        type="text"
                        name="captcha_word"
                        autocomplete="off"
                        required
                        aria-invalid=${invalid.value ? 'true' : 'false'}
                        .value=${word.value}
                        oninput=${handleInput}
                    >
                    <img
                        class=${canRefresh ? 'captcha-field__image captcha-field__image--refresh' : 'captcha-field__image'}
                        src=${imageUrl}
                        alt="Проверочное слово"
                        title=${canRefresh ? 'Обновить капчу' : 'Проверочное слово'}
                        onclick=${canRefresh ? handleRefresh : null}
                    >
                </div>
                <div class="captcha-field__error">${error.value}</div>
            </div>
        `;
    };
});