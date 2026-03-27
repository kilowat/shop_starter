defStore('smartFilter', ({ signal, computed, effect }) => {
    const result = signal(window.__SMART_FILTER__ ?? {
        PRICES: {},
        ITEMS: {},
        FILTER_URL: ''
    });

    const checkedState = signal([]); // [{ groupCode, valueId }]

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
        Object.entries(result.value.ITEMS)
            .filter(([key, item]) =>
                !(key in result.value.PRICES) &&
                Object.keys(item.VALUES || {}).length > 0
            )
            .map(([_, item]) => item)
    );

    const prices = computed(() =>
        Object.entries(result.value.ITEMS)
            .filter(([key]) => key in result.value.PRICES)
            .map(([_, item]) => item)
    );

    // --- INIT из сервера ---
    const initCheckedState = () => {
        const state = [];

        items.value.forEach(item => {
            Object.values(item.VALUES || {}).forEach(value => {
                if (value.CHECKED) {
                    state.push({
                        groupCode: item.CODE,
                        valueId: value.CONTROL_ID
                    });
                }
            });
        });

        checkedState.value = state;
    };

    // --- toggle ---
    const toggleCheck = (groupCode, valueId) => {
        const index = checkedState.value.findIndex(
            v => v.groupCode === groupCode && v.valueId === valueId
        );
        const newValue = [...checkedState.value];
        if (index > -1) {
            newValue.splice(index, 1);
            checkedState.value = newValue;
        } else {
            newValue.push({ groupCode, valueId });
            checkedState.value = newValue;
        }
        console.log(checkedState.value);
    };

    // --- helper ---
    const isChecked = (groupCode, valueId) =>
        checkedState.value.some(v =>
            v.groupCode === groupCode && v.valueId === valueId
        );

    // --- selectedGroup ---
    const selectedGroup = computed(() => {
        const groups = [];

        // --- 1. Обычные ITEMS (checkbox, radio и т.д.)
        items.value.forEach(item => {
            const values = Object.values(item.VALUES || {});

            const serverSelected = values.filter(v => v.CHECKED);

            const localSelected = values.filter(v =>
                checkedState.value.some(s =>
                    s.groupCode === item.CODE && s.valueId === v.CONTROL_ID
                )
            );

            const merged = [...serverSelected];

            localSelected.forEach(v => {
                if (!merged.find(m => m.CONTROL_ID === v.CONTROL_ID)) {
                    merged.push(v);
                }
            });

            if (merged.length > 0) {
                groups.push({
                    group: item,
                    values: merged
                });
            }
        });

        // --- 2. ЦЕНЫ (ВАЖНО!)
        prices.value.forEach(price => {
            const min = price.VALUES?.MIN;
            const max = price.VALUES?.MAX;

            const hasMin = min?.VALUE && min.VALUE !== '';
            const hasMax = max?.VALUE && max.VALUE !== '';

            if (hasMin || hasMax) {
                groups.push({
                    group: price,
                    values: [
                        {
                            type: 'range',
                            min: min?.VALUE || null,
                            max: max?.VALUE || null,
                            currency: min?.CURRENCY || max?.CURRENCY || null
                        }
                    ]
                });
            }
        });

        return groups;
    });

    // --- следим за result ---
    effect(() => {
        initCheckedState();
    });

    // --- запрос ---
    const fetchResult = async () => {
        try {
            isLoading.value = true;

            const response = await fetch(location.href, {
                headers: { ajax: 'filter' }
            });

            const { data } = await response.json();

            result.value = data.result;

            // state пересоберётся через effect

        } catch (e) {
            console.error(e);
        } finally {
            isLoading.value = false;
        }
    };

    return {
        result,
        items,
        prices,
        checkedState,
        selectedGroup,
        toggleCheck,
        isChecked,
        fetchResult,
        isLoading,
        displayTypes,
    };
});

defComponent('smart-filter', ({ html, store }) => {
    const {
        items,
        displayTypes,
        toggleCheck,
        checkedState,
        isChecked,
        selectedGroup
    } = store('smartFilter');

    const getValues = (item) => Object.values(item.VALUES || {});

    const buildCheckbox = (item) => html`
        <div class="filter-input filter--checkbox">
            ${getValues(item).map(value => html`
                <label>
                    <input 
                        type="checkbox"
                        name="${value.CONTROL_NAME}"
                        ?checked=${isChecked(item.CODE, value.CONTROL_ID)}
                        ?disabled=${value.DISABLED}
                        @change=${() => toggleCheck(item.CODE, value.CONTROL_ID)}
                    />
                    <span>${value.VALUE}</span>
                </label>
            `)}
        </div>
    `;

    const buildRadio = (item) => html`
        <div class="filter-input filter--radio">
            ${getValues(item).map(value => html`
                <label>
                    <input 
                        type="radio"
                        name="${item.CODE}"
                        ?checked=${value.CHECKED}
                    />
                    <span>${value.VALUE}</span>
                </label>
            `)}
        </div>
    `;

    const buildDropdown = (item) => html`
        <div class="filter-input filter--dropdown">
            <select>
                ${getValues(item).map(value => html`
                    <option ?selected=${value.CHECKED}>
                        ${value.VALUE}
                    </option>
                `)}
            </select>
        </div>
    `;

    const buildRange = () => html`
        <div class="filter-input filter--range">
            <input type="text" placeholder="min" />
            <input type="text" placeholder="max" />
        </div>
    `;

    const builders = {
        [displayTypes.NUMBERS_WITH_SLIDER]: buildRange,
        [displayTypes.NUMBERS]: buildRange,
        [displayTypes.RADIO_BUTTONS]: buildRadio,
        [displayTypes.DROPDOWN]: buildDropdown,
        default: buildCheckbox,
    };

    const buildInput = (item) => {
        const builder = builders[item.DISPLAY_TYPE] || builders.default;
        return builder(item);
    };

    const buildSelected = () => html`
    <div class="selected-filter">
        ${selectedGroup.value.map(group => html`
            <div class="selected-group">
                <strong>${group.group.NAME}</strong>
                ${group.values.map(value => {

        if (value.type === 'range') {
            return html`
                <button>
                    ${value.min ? `от ${value.min}` : ''}
                    ${value.max ? ` до ${value.max}` : ''}
                    ${value.currency ? ` ${value.currency}` : ''}
                </button>
            `;
        }


        return html`
                                <button>
                                    ${value.VALUE}
                                </button>
                            `;
    })}
                    </div>
                `)}
            </div>
        `;

    return () => html`
        <div class="smart-filter">
            ${JSON.stringify(checkedState.value)}
            ${buildSelected()}

            ${items.value.map(item => html`
                <div class="filter-row">
                    <div class="filter-name">${item.NAME}</div>
                    ${buildInput(item)}
                </div>
            `)}

        </div>
    `;
});