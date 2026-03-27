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
    const { items, displayTypes } = store('smartFilter');
    console.log(items.value);
    const getValues = (item) => Object.values(item.VALUES || {});

    const buildRangeSlider = (item) => html`
        <div class="filter-input filter--range-slider">
            <input type="text" placeholder="min" />
            <input type="text" placeholder="max" />
            <div class="slider"></div>
        </div>
    `;

    const buildRange = (item) => html`
        <div class="filter-input filter--range">
            <input type="text" placeholder="min" />
            <input type="text" placeholder="max" />
        </div>
    `;

    const buildCheckboxPictures = (item) => html`
        <div class="filter-input filter--checkbox-pictures">
            ${getValues(item).map(value => html`
                <label>
                    <input type="checkbox" />
                    <span class="picture"></span>
                </label>
            `)}
        </div>
    `;

    const buildCheckboxPicturesLabels = (item) => html`
        <div class="filter-input filter--checkbox-pictures-labels">
            ${getValues(item).map(value => html`
                <label>
                    <input type="checkbox" />
                    <span class="picture"></span>
                    <span>${value.VALUE}</span>
                </label>
            `)}
        </div>
    `;

    const buildDropdown = (item) => html`
        <div class="filter-input filter--dropdown">
            <select>
                ${getValues(item).map(value => html`
                    <option>${value.VALUE}</option>
                `)}
            </select>
        </div>
    `;

    const buildDropdownCustom = (item) => html`
        <div class="filter-input filter--dropdown-custom">
            <div class="dropdown">
                ${getValues(item).map(value => html`
                    <div class="dropdown-item">
                        <span class="picture"></span>
                        <span>${value.VALUE}</span>
                    </div>
                `)}
            </div>
        </div>
    `;

    const buildRadio = (item) => html`
        <div class="filter-input filter--radio">
            ${getValues(item).map(value => html`
                <label>
                    <input type="radio" name="${item.CODE}" />
                    <span>${value.VALUE}</span>
                </label>
            `)}
        </div>
    `;

    const buildCalendar = (item) => html`
        <div class="filter-input filter--calendar">
            <input type="date" />
            <input type="date" />
        </div>
    `;

    const buildCheckbox = (item) => html`
        <div class="filter-input filter--checkbox">
            ${getValues(item).map(value => html`
                <label for="${value.CONTROL_ID}">
                    <input 
                        type="checkbox"
                        name="${value.CONTROL_NAME}"
                        id="${value.CONTROL_ID}"
                        ?checked=${value.CHECKED}
                        ?disabled=${value.DISABLED}
                    />
                    <span>${value.VALUE}</span>
                </label>
            `)}
        </div>
    `;

    const builders = {
        [displayTypes.NUMBERS_WITH_SLIDER]: buildRangeSlider,
        [displayTypes.NUMBERS]: buildRange,
        [displayTypes.CHECKBOXES_WITH_PICTURES]: buildCheckboxPictures,
        [displayTypes.CHECKBOXES_WITH_PICTURES_AND_LABELS]: buildCheckboxPicturesLabels,
        [displayTypes.DROPDOWN]: buildDropdown,
        [displayTypes.DROPDOWN_WITH_PICTURES_AND_LABELS]: buildDropdownCustom,
        [displayTypes.RADIO_BUTTONS]: buildRadio,
        [displayTypes.CALENDAR]: buildCalendar,
        default: buildCheckbox,
    };

    const buildInput = (item) => {
        const builder = builders[item.DISPLAY_TYPE] || builders.default;
        return builder(item);
    };

    const buildFilterRow = (item) => html`
        <div class="filter-row">
            <div class="filter-name">${item.NAME}</div>
            ${buildInput(item)}
        </div>
    `;

    return () => html`
        <div class="smart-filter">
            ${items.value.map(buildFilterRow)}
        </div>
    `;
});
