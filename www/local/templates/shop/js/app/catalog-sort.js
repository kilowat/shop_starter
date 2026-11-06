/**
 * Сортировка каталога (signa).
 * Опции приходят в data-sorts, значение живёт в URL (?sort=price_asc).
 */
(() => {
    'use strict';

    const signa = window.signa;

    if (!signa) {
        console.error('[catalog-sort] библиотека signa не подключена');
        return;
    }

    signa.component('catalog-sort', ({ html, prop, computed }) => {
        const sorts = prop('sorts', Array, []);
        const current = computed(() => signa.location.query.value.sort || 'default');

        return () => html`
            <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0" for="catalog-sort-select">Сортировка</label>
                <select
                    id="catalog-sort-select"
                    class="form-select form-select-sm w-auto"
                    onchange=${(event) => signa.location.set({
                        sort: event.target.value === 'default' ? null : event.target.value,
                    })}
                >
                    ${sorts.value.map((sort) => html`
                        <option value=${sort.code} .selected=${sort.code === current.value}>${sort.name}</option>
                    `)}
                </select>
            </div>
        `;
    });
})();
