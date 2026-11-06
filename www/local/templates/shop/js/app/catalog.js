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

    const LIST = '[data-catalog-list]';
    const PAGE = 'PAGEN_1';

    // из каких параметров URL состоит «применённое» состояние каталога
    const isCatalogParam = (key) => (
        key === PAGE || key === 'sort' || key === 'set_filter' || key.startsWith('arrFilter')
    );

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
            const search = new URLSearchParams(query).toString();
            const url = (listNode()?.dataset.listUrl || '/api/catalog/list') + (search ? '?' + search : '');

            isLoading.value = true;

            try {
                const payload = await (await fetch(url)).json();

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

            const page = new URL(link.href, window.location.origin).searchParams.get(PAGE);

            if (!page) {
                return;
            }

            event.preventDefault();
            signa.location.set({ [PAGE]: page });
            link.closest(LIST).scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
