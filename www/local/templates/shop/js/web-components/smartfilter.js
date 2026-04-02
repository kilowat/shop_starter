// --- STORE ---
defStore('smartFilter', ({ signal, computed, effect }) => {
    const result = signal(window.__SMART_FILTER__ ?? {
        PRICES: {},
        ITEMS: {},
        FILTER_URL: ''
    });

    const checkedState = signal([]);
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
    };

    const items = computed(() =>
        Object.values(result.value.ITEMS)
            .filter(item =>
                !(item.CODE in result.value.PRICES) &&
                Object.keys(item.VALUES || {}).length > 0
            )
    );

    const prices = computed(() =>
        Object.values(result.value.ITEMS)
            .filter(item => item.CODE in result.value.PRICES)
    );

    const initCheckedState = () => {
        const state = [];
        items.value.forEach(item => {
            Object.values(item.VALUES || {}).forEach(value => {
                if (value.CHECKED) {
                    state.push({ groupCode: item.CODE, valueId: value.CONTROL_ID });
                }
            });
        });
        checkedState.value = state;
    };

    const toggleCheck = (groupCode, valueId) => {
        const index = checkedState.value.findIndex(
            v => v.groupCode === groupCode && v.valueId === valueId
        );
        const newValue = [...checkedState.value];
        if (index > -1) {
            newValue.splice(index, 1);
        } else {
            newValue.push({ groupCode, valueId });
        }
        checkedState.value = newValue;
    };

    const isChecked = (groupCode, valueId) =>
        checkedState.value.some(v => v.groupCode === groupCode && v.valueId === valueId);

    const selectedGroup = computed(() => {
        const groups = [];

        items.value.forEach(item => {
            const values = Object.values(item.VALUES || {});
            const merged = values.filter(v =>
                v.CHECKED || checkedState.value.some(s => s.groupCode === item.CODE && s.valueId === v.CONTROL_ID)
            );

            if (merged.length > 0) {
                groups.push({ group: item, values: merged });
            }
        });

        prices.value.forEach(price => {
            const min = price.VALUES?.MIN?.HTML_VALUE || price.VALUES?.MIN?.VALUE;
            const max = price.VALUES?.MAX?.HTML_VALUE || price.VALUES?.MAX?.VALUE;

            if (min || max) {
                groups.push({
                    group: price,
                    values: [{
                        type: 'range',
                        min: min || null,
                        max: max || null,
                        currency: price.VALUES?.MIN?.CURRENCY || ''
                    }]
                });
            }
        });
        return groups;
    });

    effect(() => initCheckedState());

    const fetchResult = async () => {
        try {
            isLoading.value = true;
            const response = await fetch(location.href, { headers: { ajax: 'filter' } });
            const { data } = await response.json();
            result.value = data.result;
        } catch (e) {
            console.error(e);
        } finally {
            isLoading.value = false;
        }
    };

    return {
        result, items, prices, checkedState, selectedGroup,
        toggleCheck, isChecked, fetchResult, isLoading, displayTypes
    };
});

defComponent('smart-filter', ({ html, store }) => {
    const filter = store('smartFilter');

    const helpers = {
        getValues: (item) => Object.values(item.VALUES || {}),

        buildCheckbox: (item) => html`
            <div class="filter-input filter--checkbox">
                ${helpers.getValues(item).map(value => html`
                    <label class=${value.DISABLED ? 'is-disabled' : ''}>
                        <input 
                            type="checkbox"
                            name=${value.CONTROL_NAME}
                            .checked=${filter.isChecked(item.CODE, value.CONTROL_ID)}
                            ?disabled=${value.DISABLED}
                            @change=${() => filter.toggleCheck(item.CODE, value.CONTROL_ID)}
                        />
                        <span>${value.VALUE}</span>
                    </label>
                `)}
            </div>
        `,

        buildRadio: (item) => html`
            <div class="filter-input filter--radio">
                ${helpers.getValues(item).map(value => html`
                    <label>
                        <input type="radio" name=${item.CODE} .checked=${value.CHECKED} />
                        <span>${value.VALUE}</span>
                    </label>
                `)}
            </div>
        `,

        buildDropdown: (item) => html`
            <div class="filter-input filter--dropdown">
                <select @change=${(e) => console.log('Select logic here', e.target.value)}>
                    ${helpers.getValues(item).map(value => html`
                        <option value=${value.CONTROL_ID} .selected=${value.CHECKED}>
                            ${value.VALUE}
                        </option>
                    `)}
                </select>
            </div>
        `,

        buildRange: (item) => html`
            <div class="filter-input filter--range">
                <input type="number" placeholder=${item.VALUES?.MIN?.VALUE || 'от'} />
                <input type="number" placeholder=${item.VALUES?.MAX?.VALUE || 'до'} />
            </div>
        `
    };

    const builders = {
        [filter.displayTypes.NUMBERS_WITH_SLIDER]: helpers.buildRange,
        [filter.displayTypes.NUMBERS]: helpers.buildRange,
        [filter.displayTypes.RADIO_BUTTONS]: helpers.buildRadio,
        [filter.displayTypes.DROPDOWN]: helpers.buildDropdown,
        default: helpers.buildCheckbox,
    };

    const renderSelected = () => html`
        <div class="selected-filter">
            ${filter.selectedGroup.value.map(group => html`
                <div class="selected-group">
                    <strong>${group.group.NAME}: </strong>
                    ${group.values.map(value => value.type === 'range'
        ? html`<button class="btn-tag">
                            ${value.min ? `от ${value.min}` : ''} ${value.max ? `до ${value.max}` : ''} ${value.currency}
                          </button>`
        : html`<button class="btn-tag" @click=${() => filter.toggleCheck(group.group.CODE, value.CONTROL_ID)}>
                            ${value.VALUE} ✕
                          </button>`
    )}
                </div>
            `)}
        </div>
    `;

    return () => html`
        <div class="smart-filter ${filter.isLoading.value ? 'is-loading' : ''}">
            ${renderSelected()}
            
            <div class="filter-items">
                ${filter.items.value.map(item => html`
                    <div class="filter-item" data-code=${item.CODE}>
                        <div class="filter-item__title">${item.NAME}</div>
                        <div class="filter-item__content">
                            ${(builders[item.DISPLAY_TYPE] || builders.default)(item)}
                        </div>
                    </div>
                `)}
            </div>

            <div class="filter-actions">
                <button @click=${filter.fetchResult}>Показать</button>
            </div>
        </div>
    `;
});
