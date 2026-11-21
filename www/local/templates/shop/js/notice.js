signa.state('notice', ({ signal }) => {
    const POSITIONS = [
        'top-left',
        'top-center',
        'top-right',
        'bottom-left',
        'bottom-center',
        'bottom-right',
    ];
    const TYPES = ['success', 'error', 'info'];

    const settings = {
        position: 'top-right',
        timeout: 5000,
        closable: true,
    };

    const current = signal(null);

    let closeTimer = null;
    let progressTimer = null;
    let sequence = 0;

    const clearTimers = () => {
        if (closeTimer !== null) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }

        if (progressTimer !== null) {
            clearInterval(progressTimer);
            progressTimer = null;
        }
    };

    const close = () => {
        clearTimers();
        current.value = null;
    };

    const show = (options = {}) => {
        const text = String(options.text ?? '').trim();

        if (!text) {
            return null;
        }

        const id = ++sequence;
        const timeout = Number.isFinite(options.timeout)
            ? Math.max(0, options.timeout)
            : settings.timeout;
        const closable = typeof options.closable === 'boolean'
            ? options.closable
            : settings.closable;
        const position = POSITIONS.includes(options.position)
            ? options.position
            : settings.position;
        const type = TYPES.includes(options.type)
            ? options.type
            : 'info';

        clearTimers();

        current.value = {
            id,
            type,
            text,
            position,
            timeout,
            closable,
            progress: 100,
        };

        if (timeout > 0) {
            const startedAt = Date.now();

            closeTimer = setTimeout(() => {
                if (current.value?.id === id) {
                    close();
                }
            }, timeout);

            progressTimer = setInterval(() => {
                if (current.value?.id !== id) {
                    return;
                }

                const elapsed = Date.now() - startedAt;
                const progress = Math.max(0, 100 - (elapsed / timeout) * 100);

                current.value = { ...current.value, progress };
            }, 50);
        }

        return id;
    };

    const dismiss = (id) => {
        if (id !== undefined && current.value?.id !== id) {
            return;
        }

        close();
    };

    const configure = (options = {}) => {
        if (POSITIONS.includes(options.position)) {
            settings.position = options.position;
        }

        if (Number.isFinite(options.timeout)) {
            settings.timeout = Math.max(0, options.timeout);
        }

        if (typeof options.closable === 'boolean') {
            settings.closable = options.closable;
        }
    };

    const withType = (type) => (text, options = {}) => show({ ...options, type, text });

    return {
        current,
        configure,
        show,
        close,
        dismiss,
        success: withType('success'),
        error: withType('error'),
        info: withType('info'),
    };
});

signa.component('notice-toast', ({ html }, { notice }) => () => {
    const item = notice.current.value;

    return html`
        ${item ? html`
            <div
                class=${`notice notice--${item.type} notice--${item.position}`}
                role=${item.type === 'error' ? 'alert' : 'status'}
            >
                <div class="notice__body">
                    <p class="notice__text">${item.text}</p>
                    ${item.closable ? html`
                        <button
                            class="notice__close"
                            type="button"
                            aria-label="Закрыть"
                            onclick=${() => notice.dismiss(item.id)}
                        >×</button>
                    ` : ''}
                </div>
                ${item.timeout > 0 ? html`
                    <div class="notice__progress" aria-hidden="true">
                        <span class="notice__progress-fill" style=${`width: ${item.progress}%`}></span>
                    </div>
                ` : ''}
            </div>
        ` : ''}
    `;
});