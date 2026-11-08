/**
 * Каталог: состояние живёт в URL.
 *
 * catalogState слушает signa.location и подменяет HTML списка
 * (грид + пагинация приходят из компонента), не перезагружая страницу.
 */
(() => {
    'use strict';

    const signa = window.signa;

    if (!signa) {
        console.error('[catalog] библиотека signa не подключена');
        return;
    }

    const LIST = '#catalog-list';

    /**
     * Имя параметра сортировки: его задаёт shop:catalog.sort
     * (параметр SORT_PARAM_NAME) через data-param у <select>.
     * Без компонента сортировки — 'sort'.
     */
    const sortParam = () => (
        document.querySelector('[data-sort]')?.dataset.param || 'sort'
    );

    // из каких параметров URL состоит «применённое» состояние каталога
    const isCatalogParam = (key) => (
        key === sortParam() || key === 'set_filter' || key.startsWith('arrFilter')
    );

    /** CSRF-токен из header.php. */
    const sessid = () => window.__BITRIX_SESSID__ || '';

    /** POST-запрос на текущую страницу каталога. */
    const post = async (url, data) => {
        const body = new URLSearchParams();

        Object.entries(data).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                body.set(key, value);
            }
        });

        const response = await fetch(url, {
            method: 'POST',
            body,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        return response.json();
    };

    /**
     * Прокрутка к началу выдачи после фильтра или сортировки.
     * Если пользователь выше цели — не трогаем: вниз не скроллим.
     */
    const scrollToStart = () => {
        const target = document.getElementById('catalog-results') || document.querySelector(LIST);
        const top = Math.max(
            (target ? target.getBoundingClientRect().top : 0) + window.scrollY - 16,
            0
        );

        if (window.scrollY <= top + 1) {
            return;
        }

        const reduceMotion = window.matchMedia
            && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        window.scrollTo({ top, behavior: reduceMotion ? 'auto' : 'smooth' });
    };

    signa.state('catalogState', ({ signal, computed, effect }) => {
        const listNode = () => document.querySelector(LIST);
        const html = signal('');
        const isLoading = signal(false);

        const params = computed(() => Object.fromEntries(
            Object.entries(signa.location.query.value).filter(([key]) => isCatalogParam(key))
        ));

        let renderedKey = null;
        let requestId = 0;

        const load = async (query, key) => {
            const id = ++requestId;
            const search = new URLSearchParams(query);

            isLoading.value = true;

            try {
                const url = window.location.pathname + (search.toString() ? '?' + search.toString() : '');
                const payload = await post(url, {
                    sessid: sessid(),
                    ajax_action: 'list',
                });

                if (id !== requestId || payload.status !== 'success') {
                    return;
                }

                html.value = payload.data.html;
                renderedKey = key;

                // к началу выдачи: разметка могла не измениться (тот же порядок),
                // поэтому скроллим здесь, а не в эффекте подмены. setTimeout, а не
                // requestAnimationFrame: кадр может не прийти, если ничего не перерисовалось
                window.setTimeout(scrollToStart, 0);
            } catch (error) {
                console.warn('[catalog] не удалось загрузить список:', error);
            } finally {
                if (id === requestId) {
                    isLoading.value = false;
                }
            }
        };

        // URL изменился -> запрашиваем список
        effect(() => {
            const key = JSON.stringify(params.value);

            if (renderedKey === null) {
                renderedKey = key; // на старте в DOM уже серверная разметка
                return;
            }

            if (key !== renderedKey) {
                load(params.value, key);
            }
        });

        // ответ -> в DOM
        effect(() => {
            const node = listNode();

            if (!node) {
                return;
            }

            if (html.value && node.innerHTML !== html.value) {
                node.innerHTML = html.value;
            }

            node.classList.toggle('is-loading', isLoading.value);
        });

        return { html, isLoading };
    });

    // Постраничная навигация больше не живёт в этом файле: за неё отвечает
    // отдельный компонент bitrix:system.pagenavigation и js/pagination/pagination.js.
    const start = () => {
        signa.state('catalogState');
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
