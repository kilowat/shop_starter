/**
 * Выбранные значения фильтра: чипы в порядке выбора.
 * Клик по крестику применяет фильтр сразу, без скролла страницы.
 */
(() => {
    'use strict';

    const signa = window.signa;

    if (!signa) {
        console.error('[catalog-selected] библиотека signa не подключена');
        return;
    }

    signa.component('catalog-selected', ({ html }, states) => {
        const filter = states.smartFilter;

        return () => html`
            <div class="catalog-selected d-flex flex-wrap gap-2">
                ${filter.selected.value.map((chip) => html`
                    <button
                        type="button"
                        class="btn btn-sm btn-light border d-inline-flex align-items-center gap-1"
                        onclick=${() => filter.removeChip(chip)}
                    >
                        <span class="text-muted small">${chip.groupName}:</span>
                        <span>${chip.text}</span>
                        <span aria-hidden="true">✕</span>
                    </button>
                `)}
            </div>
        `;
    });
})();
