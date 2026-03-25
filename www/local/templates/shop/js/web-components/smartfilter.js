defStore('smartFilter', ({ signal, computed, effect }) => {
    const result = signal(window.__SMART_FILTER__ ?? { 'PRICES': {}, 'ITEMS': {}, 'FILTER_URL': '' });

    const isLoading = signal(false);

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
    fetchResult();

    return {
        prices,
        items,
        isLoading,
    }
});

defComponent('smart-filter', ({ html, prop, store }) => {
    const { items, prices } = store('smartFilter');
    const buildFilters = () => html`
    
    `

    return () => html`
        <div class="smart-filter">
            ${buildFilters()}
        </div>`;
})