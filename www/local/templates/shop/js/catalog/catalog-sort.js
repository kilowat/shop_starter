/**
 * Сортировка каталога (signa).
 *
 * Конфигурация приходит из шаблона компонента shop:catalog.sort:
 * JSON-скрипт .catalog-sort__config рядом с элементом <catalog-sort>.
 */
(() => {
    'use strict';

    const signa = window.signa;

    if (!signa) {
        console.error('[catalog-sort] библиотека signa не подключена');
        return;
    }

    const readConfig = (root) => {
        const node = root?.querySelector('script.catalog-sort__config');

        if (!node) {
            return {};
        }

        try {
            return JSON.parse(node.textContent || '{}');
        } catch (error) {
            console.error('[catalog-sort] не удалось прочитать конфигурацию:', error);
            return {};
        }
    };

    signa.component('catalog-sort', ({ html, $this }) => {
        const config = readConfig($this.closest('.catalog-sort'));

        const options = Array.isArray(config.options) ? config.options : [];
        const param = config.param || 'sort';
        const defaultValue = config.default || 'default';
        const title = config.title || 'Сортировка';
        const showTitle = config.showTitle !== false;

        return () => {
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
