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

    /** Имя параметра постраничной навигации каталога. */
    const PAGE_PARAM = 'PAGEN_1';
    const pageParam = () => PAGE_PARAM;

    // из каких параметров URL состоит «применённое» состояние каталога
    const isCatalogParam = (key) => (
        key === pageParam() || key === 'sort' || key === 'set_filter' || key.startsWith('arrFilter')
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

    // Ссылки пагинации приходят вместе с HTML — перехватываем клики,
    // чтобы не было полной перезагрузки
    const start = () => {
        signa.state('catalogState');

        document.addEventListener('click', (event) => {
            const link = event.target.closest(`${LIST} a[href]`);

            if (!link) {
                return;
            }

            const page = new URL(link.href, window.location.origin).searchParams.get(pageParam());

            if (!page) {
                return;
            }

            event.preventDefault();
            signa.location.set({ [pageParam()]: page });
            link.closest(LIST).scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
