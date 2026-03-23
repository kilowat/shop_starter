defStore('smartFilter', ({ signal, computed }) => {
    const result = signal(window.__SMART_FILTER__ ?? { 'PRICES': {}, 'ITEMS': {} });

    const payload = signal([]);

    const prices = computed(() =>
        Object.entries(result.value.ITEMS).reduce((acc, [key, value]) => {
            if (key in result.value.PRICES) acc[key] = value;
            return acc;
        }, {})
    );

    const items = computed(() =>
        Object.entries(result.value.ITEMS).reduce((acc, [key, value]) => {
            if (!(key in result.value.PRICES)) acc[key] = value;
            return acc;
        }, {})
    );

    return {
        result,
        payload,
        prices,
        items
    }
});

defComponent('smart-filter', ({ html, prop, store }) => {
    const { items, prices } = store('smartFilter');
    console.log(prices.value);
    const buildFilters = () => html`
    
    `

    return () => html`
        <div class="smart-filter">
            ${buildFilters()}
        </div>`;
})