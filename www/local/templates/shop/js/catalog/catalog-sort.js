/**
 * Сортировка каталога на signa.
 *
 * Данные приходят из signa-state SortState (их выставляет template.php
 * компонента shop:catalog.sort). Верстку рисует <catalog-sort>.
 */
(() => {
    'use strict';

    const signa = window.signa;

    if (!signa) {
        console.error('[catalog-sort] библиотека signa не подключена');
        return;
    }

    signa.state('SortState', ({ signal }) => signal({
        options: [],
        param: 'sort',
        default: 'default',
        title: 'Сортировка',
        showTitle: true,
    }));

    signa.component('catalog-sort', ({ html }, states) => {
        const sortState = states.SortState;

        return () => {
            const state = sortState.value || {};
            const options = Array.isArray(state.options) ? state.options : [];
            const param = state.param || 'sort';
            const defaultValue = state.default || 'default';
            const title = state.title || 'Сортировка';
            const showTitle = state.showTitle !== false;
            const current = signa.location.query.value[param] || defaultValue;

            return html`
                <div class="d-flex align-items-center gap-2">
                    ${showTitle
                        ? html`<label class="small text-muted mb-0" for="catalog-sort-select">${title}</label>`
                        : null}
                    <select
                        id="catalog-sort-select"
                        class="form-select form-select-sm w-auto"
                        onchange=${(event) => signa.location.set({
                            [param]: event.target.value === defaultValue ? null : event.target.value,
                        })}
                    >
                        ${options.map((sort) => html`
                            <option value=${sort.code} .selected=${sort.code === current}>${sort.name}</option>
                        `)}
                    </select>
                </div>
            `;
        };
    });
})();
