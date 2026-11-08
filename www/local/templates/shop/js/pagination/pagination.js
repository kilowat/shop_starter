/**
 * Универсальная постраничная навигация.
 *
 * Список размечивается в своём шаблоне тремя атрибутами:
 *
 *   <div data-pagination-list>          — контейнер списка
 *       <div data-pagination-items>     — куда дописывать элементы
 *           <article data-pagination-item>...</article>
 *       </div>
 *   </div>
 *
 * Пагинатор (system.pagenavigation) выводится внутри [data-pagination-list]
 * и содержит data-pagination + data-mode. Режимы:
 *   classic   — ничего не делает, ссылки работают как обычные;
 *   show_more — кнопка [data-pagination-more], ответ дописывается в items;
 *   lazy      — IntersectionObserver на [data-pagination-sentinel].
 *
 * Следующая страница запрашивается обычным GET по data-next-url, из HTML
 * берётся [data-pagination-list] -> [data-pagination-items], а из него —
 * элементы [data-pagination-item]. Специальный AJAX-экшен серверу не нужен.
 *
 * Настройки конкретного шаблона (подписи, спиннер, rootMargin) живут
 * в самом шаблоне пагинатора, а не в параметрах компонента.
 */
(() => {
    'use strict';

    const ROOT = '[data-pagination]';
    const LIST = '[data-pagination-list]';
    const ITEMS = '[data-pagination-items]';
    const ITEM = '[data-pagination-item]';
    const instances = new WeakMap();

    const toInt = (value, fallback) => {
        const number = Number.parseInt(value, 10);

        return Number.isFinite(number) ? number : fallback;
    };

    const cssEscape = (value) => (
        window.CSS && typeof window.CSS.escape === 'function'
            ? window.CSS.escape(value)
            : String(value).replace(/(["\\])/g, '\\$1')
    );

    const cssQuote = (value) => String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');

    class Pagination {
        constructor(root) {
            this.root = root;
            this.mode = root.dataset.mode || 'classic';
            this.page = Math.max(1, toInt(root.dataset.page, 1));
            this.totalPages = Math.max(1, toInt(root.dataset.totalPages, 1));
            this.nextUrl = (root.dataset.nextUrl || '').trim();
            this.pageParam = root.dataset.pageParam || 'PAGEN_1';
            this.rootMargin = root.dataset.rootMargin || '400px';
            this.labelMore = root.dataset.labelMore || 'Показать ещё';
            this.labelLoading = root.dataset.labelLoading || 'Загрузка…';
            this.labelDone = root.dataset.labelDone || 'Показано всё';
            this.labelError = root.dataset.labelError || 'Не удалось загрузить. Попробуйте ещё раз.';

            this.isLoading = false;
            this.isDone = this.nextUrl === '' || this.page >= this.totalPages;
            this.destroyed = false;
            this.observer = null;
            this.button = null;
            this.scrollScheduled = false;

            this.onClick = this.onClick.bind(this);
            this.onScroll = this.onScroll.bind(this);

            if (this.mode === 'show_more') {
                this.initMore();
            } else if (this.mode === 'lazy') {
                this.initLazy();
            }
        }

        /* ---------- список ---------- */

        getList() {
            let node = this.root.parentElement;

            while (node && node !== document.documentElement) {
                if (node.hasAttribute('data-pagination-list')) {
                    return node;
                }

                node = node.parentElement;
            }

            return null;
        }

        getItems(list = this.getList()) {
            return list ? list.querySelector(ITEMS) : null;
        }

        /**
         * Селектор, который найдёт этот же список в HTML следующей страницы.
         */
        describeList(list) {
            if (list.id) {
                return '#' + cssEscape(list.id);
            }

            const key = list.dataset.paginationKey;

            if (key) {
                return `[data-pagination-list][data-pagination-key="${cssQuote(key)}"]`;
            }

            return LIST;
        }

        /* ---------- инициализация ---------- */

        initMore() {
            this.button = this.root.querySelector('[data-pagination-more]');

            if (this.isDone) {
                this.finish();
                return;
            }

            if (this.button) {
                this.button.addEventListener('click', this.onClick);
            }

            this.updateStatus();
        }

        initLazy() {
            this.sentinel = this.root.querySelector('[data-pagination-sentinel]') || this.root;

            if (this.isDone) {
                this.finish();
                return;
            }

            if ('IntersectionObserver' in window) {
                this.observer = new IntersectionObserver((entries) => {
                    if (entries.some((entry) => entry.isIntersecting)) {
                        this.loadNext();
                    }
                }, {
                    root: null,
                    rootMargin: this.rootMargin,
                    threshold: 0,
                });

                this.observer.observe(this.sentinel);
            } else {
                window.addEventListener('scroll', this.onScroll, { passive: true });
                this.onScroll();
            }

            this.updateStatus();
        }

        /* ---------- события ---------- */

        onClick(event) {
            const button = event.target.closest('[data-pagination-more]');

            if (!button || !this.root.contains(button)) {
                return;
            }

            // Средний клик, Ctrl/Cmd/Shift — пусть браузер откроет ссылку сам.
            if (event.defaultPrevented || event.button !== 0
                || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            event.preventDefault();
            this.loadNext();
        }

        onScroll() {
            if (this.scrollScheduled || this.isLoading || this.isDone) {
                return;
            }

            this.scrollScheduled = true;

            window.requestAnimationFrame(() => {
                this.scrollScheduled = false;

                if (this.isLoading || this.isDone || !this.sentinel) {
                    return;
                }

                const rect = this.sentinel.getBoundingClientRect();

                if (rect.top <= window.innerHeight + (Number.parseFloat(this.rootMargin) || 0)) {
                    this.loadNext();
                }
            });
        }

        /* ---------- загрузка ---------- */

        async loadNext() {
            if (this.destroyed || this.isLoading || this.isDone || this.nextUrl === '') {
                return;
            }

            const list = this.getList();
            const items = this.getItems(list);

            if (!list || !items) {
                console.warn('[pagination] не найден [data-pagination-list] или [data-pagination-items]', this.root);
                this.showError();
                return;
            }

            const listSelector = this.describeList(list);
            const requestUrl = this.nextUrl;

            this.isLoading = true;
            this.setBusy(true);

            try {
                const response = await fetch(requestUrl, {
                    credentials: 'same-origin',
                    headers: { Accept: 'text/html' },
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const html = await response.text();

                if (this.destroyed) {
                    return;
                }

                const doc = new DOMParser().parseFromString(html, 'text/html');
                const fetchedList = doc.querySelector(listSelector);

                if (!fetchedList) {
                    throw new Error(`в ответе нет списка по селектору ${listSelector}`);
                }

                const fetchedItems = fetchedList.querySelector(ITEMS);

                if (!fetchedItems) {
                    throw new Error('в ответе нет [data-pagination-items]');
                }

                const nodes = Array.from(fetchedItems.querySelectorAll(ITEM));

                if (nodes.length === 0) {
                    this.nextUrl = '';
                    this.finish();
                    return;
                }

                const fragment = document.createDocumentFragment();

                nodes.forEach((node) => {
                    fragment.appendChild(document.importNode(node, true));
                });

                items.appendChild(fragment);

                const fetchedPager = fetchedList.querySelector(ROOT);

                if (fetchedPager) {
                    this.page = Math.max(1, toInt(fetchedPager.dataset.page, this.page + 1));
                    this.totalPages = Math.max(
                        1,
                        toInt(fetchedPager.dataset.totalPages, this.totalPages)
                    );
                    this.nextUrl = (fetchedPager.dataset.nextUrl || '').trim();
                } else {
                    this.page += 1;
                    this.nextUrl = this.page < this.totalPages
                        ? this.incrementUrl(requestUrl, this.page + 1)
                        : '';
                }

                this.root.dataset.page = String(this.page);
                this.root.dataset.totalPages = String(this.totalPages);
                this.root.dataset.nextUrl = this.nextUrl;

                this.updateStatus();

                this.root.dispatchEvent(new CustomEvent('pagination:loaded', {
                    bubbles: true,
                    detail: { page: this.page, url: requestUrl },
                }));

                if (this.nextUrl === '' || this.page >= this.totalPages) {
                    this.finish();
                } else if (this.mode === 'lazy') {
                    // Если после дописывания sentinel всё ещё виден — грузим дальше.
                    window.setTimeout(() => this.maybeLoadAgain(), 50);
                }
            } catch (error) {
                console.warn('[pagination] не удалось загрузить страницу:', error);
                this.showError();
            } finally {
                this.isLoading = false;

                if (!this.isDone) {
                    this.setBusy(false);
                }
            }
        }

        incrementUrl(url, targetPage) {
            try {
                const next = new URL(url, window.location.href);
                next.searchParams.set(this.pageParam, String(targetPage));

                return next.pathname + next.search + next.hash;
            } catch (error) {
                return '';
            }
        }

        maybeLoadAgain() {
            if (this.destroyed || this.isLoading || this.isDone || this.nextUrl === '' || !this.sentinel) {
                return;
            }

            const rect = this.sentinel.getBoundingClientRect();

            if (rect.top <= window.innerHeight + (Number.parseFloat(this.rootMargin) || 0)) {
                this.loadNext();
            }
        }

        /* ---------- состояние ---------- */

        setBusy(state) {
            this.root.classList.toggle('is-loading', state);
            this.root.setAttribute('aria-busy', state ? 'true' : 'false');

            if (!this.button) {
                return;
            }

            this.button.classList.toggle('disabled', state);
            this.button.setAttribute('aria-disabled', state ? 'true' : 'false');

            const spinner = this.button.querySelector('[data-pagination-spinner]');

            if (spinner) {
                spinner.classList.toggle('d-none', !state);
            }

            const label = this.button.querySelector('[data-pagination-label]');

            if (label) {
                label.textContent = state
                    ? this.labelLoading
                    : (this.isDone ? this.labelDone : this.labelMore);
            }
        }

        updateStatus() {
            const status = this.root.querySelector('[data-pagination-status]');

            if (!status) {
                return;
            }

            const items = this.getItems();
            const shown = items
                ? items.querySelectorAll(ITEM).length
                : toInt(this.root.dataset.shownItems, 0);
            const total = toInt(this.root.dataset.totalItems, 0);

            if (total > 0) {
                status.textContent = `Показано ${shown} из ${total}`;
            } else {
                status.textContent = this.isDone ? this.labelDone : `Показано ${shown}`;
            }
        }

        showError() {
            const status = this.root.querySelector('[data-pagination-status]');

            if (status) {
                status.textContent = this.labelError;
            }
        }

        finish() {
            this.isDone = true;
            this.nextUrl = '';
            this.root.dataset.nextUrl = '';
            this.root.classList.add('is-done');
            this.root.classList.remove('is-loading');

            if (this.button) {
                this.button.classList.add('d-none');
                this.button.setAttribute('aria-hidden', 'true');
                this.button.removeEventListener('click', this.onClick);
            }

            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }

            window.removeEventListener('scroll', this.onScroll);

            this.updateStatus();
        }

        destroy() {
            this.destroyed = true;
            this.root.removeEventListener('click', this.onClick);
            window.removeEventListener('scroll', this.onScroll);

            if (this.button) {
                this.button.removeEventListener('click', this.onClick);
            }

            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }
        }
    }

    const init = (root) => {
        if (!(root instanceof Element) || instances.has(root)) {
            return;
        }

        instances.set(root, new Pagination(root));
    };

    const destroy = (root) => {
        const instance = instances.get(root);

        if (!instance) {
            return;
        }

        instance.destroy();
        instances.delete(root);
    };

    const initWithin = (scope) => {
        if (!scope) {
            return;
        }

        if (scope.nodeType === Node.ELEMENT_NODE && scope.matches(ROOT)) {
            init(scope);
        }

        if (typeof scope.querySelectorAll === 'function') {
            scope.querySelectorAll(ROOT).forEach(init);
        }
    };

    const destroyWithin = (scope) => {
        if (!scope) {
            return;
        }

        if (scope.nodeType === Node.ELEMENT_NODE && scope.matches(ROOT)) {
            destroy(scope);
        }

        if (typeof scope.querySelectorAll === 'function') {
            scope.querySelectorAll(ROOT).forEach(destroy);
        }
    };

    const start = () => {
        initWithin(document);

        // catalog.js (фильтр, сортировка) заменяет HTML списка целиком,
        // поэтому пагинатор переинициализируем на каждой вставке.
        if ('MutationObserver' in window) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            initWithin(node);
                        }
                    });

                    mutation.removedNodes.forEach((node) => {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            destroyWithin(node);
                        }
                    });
                });
            });

            observer.observe(document.documentElement, {
                childList: true,
                subtree: true,
            });
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }

    // Ручная переинициализация — если список отрисован без замены DOM.
    window.shopPagination = {
        init: () => initWithin(document),
    };
})();
