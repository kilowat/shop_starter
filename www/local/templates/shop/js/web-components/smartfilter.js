defStore('smartFilter', ({ signal, computed, effect, store }) => {
    const result = signal(window.__SMART_FILTER__ ?? { 'PRICES': {}, 'ITEMS': {}, 'FILTER_URL': '' });

    const isLoading = signal(false);

    const displayTypes = {
        NUMBERS_WITH_SLIDER: 'A',
        NUMBERS: 'B',
        CHECKBOXES_WITH_PICTURES: 'G',
        CHECKBOXES_WITH_PICTURES_AND_LABELS: 'H',
        DROPDOWN: 'P',
        DROPDOWN_WITH_PICTURES_AND_LABELS: 'R',
        RADIO_BUTTONS: 'K',
        CALENDAR: 'U',
    }

    const prices = computed(() =>
        Object.entries(result.value.ITEMS)
            .filter(([key]) => key in result.value.PRICES)
            .map(([key, item]) => item)
    );

    const items = computed(() =>
        Object.entries(result.value.ITEMS)
            .filter(([key, item]) =>
                !(key in result.value.PRICES) &&
                Object.keys(item.VALUES || {}).length > 0
            )
            .map(([key, item]) => item)
    );

    const fetchResult = async () => {
        try {
            isLoading.value = true;
            const response = await fetch(location.href, { headers: { 'ajax': 'filter' } });
            const { data } = await response.json();
            result.value = data.result;
            console.log(result.value);
        } catch (e) {
            console.error(e);
        } finally {
            isLoading.value = false;
        }
    }

    return {
        result,
        prices,
        items,
        isLoading,
        displayTypes,
    }
});

defComponent('smart-filter', ({ html, store }) => {
    const { items, prices, displayTypes } = store('smartFilter');

    const buildInputRow = (item) => {
        switch (item.DISPLAY_TYPE) {
            default: return html`checkobx`
        }
    }

    const buildFilterRow = (item) => html`
        <div class="filter-row">
            <pre>${JSON.stringify(item)}</pre>
            ${buildInputRow(item)}
        </div>
    `;

    return () => html`
        <div class="smart-filter">
            ${items.value.map(buildFilterRow)}
        </div>`;
})

