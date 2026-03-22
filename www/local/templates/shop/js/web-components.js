defStore('smartFilter', ({ signal }) => {
    const result = signal(window.__SMART_FILTER__);
    const selectedFilters = signal(0);

    return {
        result,
        selectedFilters,
    }
})


defComponent('smart-filter', ({ html, prop, store }) => {
    const { selectedFilters } = store('smartFilter');
    return () => html`<button onclick=${() => selectedFilters.value++}> smart-filter ${selectedFilters.value} </button>`;
})