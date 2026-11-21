/**
 * Модальное окно signa (адаптация примера из lib/signa/docs/src/popup.js).
 *
 * Использование:
 *   <popup-trigger data-component="auth-form" data-backurl="...">...</popup-trigger>
 *   <popup-window></popup-window>
 */
signa.component('popup-window', ({ signal, html, bus, effect, $this }) => {
    const isOpen = signal(false);
    const title = signal('');
    const body = signal('');
    const content = signal(null);
    const onClose = signal(null);
    const autoClose = signal(0);

    let startY = 0;
    let currentY = 0;
    let isDragging = false;
    let timer = null;
    let movedNode = null;
    let placeholder = null;

    const SLIDE_THRESHOLD = 80;

    const getWindowEl = () => $this.querySelector('.popup__window');
    const getContentEl = () => $this.querySelector('.popup__content');

    const resetPosition = () => {
        const el = getWindowEl();
        if (!el) return;
        el.style.transform = '';
        el.style.transition = '';
    };

    const mountContent = () => {
        const node = content.value;
        if (!node || typeof node === 'string' || typeof node === 'function') return;
        const container = getContentEl();
        if (!container) return;

        movedNode = node;
        if (!movedNode.parentNode) {
            container.appendChild(movedNode);
            return;
        }
        placeholder = document.createComment('popup-placeholder');
        movedNode.parentNode.insertBefore(placeholder, movedNode);
        container.appendChild(movedNode);
    };

    const restoreContent = () => {
        if (!movedNode) return;
        if (!placeholder) { movedNode.remove(); movedNode = null; return; }
        placeholder.parentNode.insertBefore(movedNode, placeholder);
        placeholder.remove();
        movedNode = null;
        placeholder = null;
    };

    const close = () => {
        isOpen.value = false;
        document.body.style.overflow = '';
        if (timer) { clearTimeout(timer); timer = null; }
        resetPosition();
        restoreContent();
        currentY = 0;
        isDragging = false;
        if (onClose.value) { onClose.value(); onClose.value = null; }
    };

    const open = (opts = {}) => {
        close();
        title.value = opts.title || '';
        body.value = opts.body || '';
        content.value = opts.content || null;
        onClose.value = opts.onClose || null;
        autoClose.value = opts.autoClose || 0;
        isOpen.value = true;
        document.body.style.overflow = 'hidden';

        if (autoClose.value > 0) {
            timer = setTimeout(close, autoClose.value);
        }

        requestAnimationFrame(() => { resetPosition(); mountContent(); });
    };

    // touch swipe-to-close
    const onTouchStart = (e) => {
        const el = getContentEl();
        if (el && el.scrollTop > 0) return;
        startY = e.touches[0].clientY;
        isDragging = true;
    };

    const onTouchMove = (e) => {
        if (!isDragging) return;
        const delta = e.touches[0].clientY - startY;
        if (delta < 0) return;
        currentY = delta;
        const el = getWindowEl();
        if (!el) return;
        el.style.transition = 'none';
        el.style.transform = `translateY(${delta}px)`;
    };

    const onTouchEnd = () => {
        isDragging = false;
        const el = getWindowEl();
        if (!el) return;
        el.style.transition = '';
        if (currentY > SLIDE_THRESHOLD) close();
        else el.style.transform = '';
        currentY = 0;
    };

    // bus events
    effect(() => bus.on('popup:open', open));
    effect(() => bus.on('popup:close', close));

    // bind touch when open
    effect(() => {
        if (!isOpen.value) return;
        const el = getWindowEl();
        if (!el) return;
        el.addEventListener('touchstart', onTouchStart, { passive: true });
        el.addEventListener('touchmove', onTouchMove, { passive: false });
        el.addEventListener('touchend', onTouchEnd);
        return () => {
            el.removeEventListener('touchstart', onTouchStart);
            el.removeEventListener('touchmove', onTouchMove);
            el.removeEventListener('touchend', onTouchEnd);
        };
    });

    // esc key
    effect(() => {
        const onKey = (e) => { if (e.key === 'Escape' && isOpen.value) close(); };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    });

    $this.open = open;
    $this.close = close;

    const renderContent = () => {
        const c = content.value;
        if (typeof c === 'function') return c();
        if (typeof c === 'string') return html([c]);
        return html`
            ${title.value ? html`<h2>${title.value}</h2>` : ''}
            ${body.value ? html`<div>${body.value}</div>` : ''}
        `;
    };

    return () => html`
        <div class=${isOpen.value ? 'popup popup--open' : 'popup'}
             style=${isOpen.value ? 'display:flex' : 'display:none'}>
            <div class="popup__overlay" onclick=${close}></div>
            <div class="popup__window">
                <button type="button" class="popup__close" aria-label="Закрыть" onclick=${close}></button>
                <div class="popup__content">
                    ${renderContent()}
                </div>
            </div>
        </div>
    `;
});

signa.component('popup-trigger', ({ prop, bus, effect, $this }) => {
    const contentId = prop('contentId');
    const componentName = prop('component');

    const SKIP = ['data-component', 'data-content-id'];

    effect(() => {
        const onClick = (e) => {
            let content = null;

            if (componentName.value) {
                const el = document.createElement(componentName.value);
                $this.getAttributeNames()
                    .filter(a => a.startsWith('data-') && !SKIP.includes(a))
                    .forEach(a => el.setAttribute(a, $this.getAttribute(a)));
                content = el;
            } else if (contentId.value) {
                content = document.getElementById(contentId.value);
            }

            if (content) {
                e.preventDefault();
                bus.emit('popup:open', { content });
            }
        };

        $this.addEventListener('click', onClick);
        return () => $this.removeEventListener('click', onClick);
    });
});