/**
 * Умный фильтр каталога на signa + Bootstrap 5 + noUiSlider.
 *
 * Данные приходят из PHP: window.__SMART_FILTER__
 * (шаблон components/bitrix/catalog.smart.filter/signa).
 */
(() => {
    'use strict';

    const signa = window.signa;

    if (!signa) {
        console.error('[smart-filter] библиотека signa не подключена');
        return;
    }

    const DISPLAY_TYPES = {
        NUMBERS_WITH_SLIDER: 'A',
        NUMBERS: 'B',
        CHECKBOXES_WITH_PICTURES: 'G',
        CHECKBOXES_WITH_PICTURES_AND_LABELS: 'H',
        RADIO_BUTTONS: 'K',
        DROPDOWN: 'P',
        DROPDOWN_WITH_PICTURES_AND_LABELS: 'R',
        CALENDAR: 'U'
    };

    const isFilled = (value) => value !== undefined && value !== null && value !== '';

    const toNumber = (value) => {
        const normalized = String(value === undefined || value === null ? '' : value)
            .replace(/\s/g, '')
            .replace(',', '.');
        const parsed = Number(normalized);

        return Number.isFinite(parsed) ? parsed : 0;
    };

    const formatMoney = (value, currency, decimals) => {
        try {
            return new Intl.NumberFormat('ru-RU', {
                style: currency ? 'currency' : 'decimal',
                currency: currency || undefined,
                maximumFractionDigits: decimals || 0,
                minimumFractionDigits: 0
            }).format(value);
        } catch (e) {
            return String(value);
        }
    };

    const isRangeItem = (item) => Boolean(item && item.VALUES && item.VALUES.MIN && item.VALUES.MAX);

    // --------------------------------------------------------------- store
    signa.state('smartFilter', ({ signal, computed }) => {
        const source = window.__SMART_FILTER__ || {};

        const data = signal({
            ITEMS: source.ITEMS || {},
            PRICES: source.PRICES || {},
            FILTER_NAME: source.FILTER_NAME || '',
            FORM_ACTION: source.FORM_ACTION || '',
            FILTER_URL: source.FILTER_URL || ''
        });

        const buildItems = () => Object.values(data.value.ITEMS || {}).filter(
            (item) => !isRangeItem(item) && Object.keys(item.VALUES || {}).length > 0
        );

        const buildRanges = (sourceItems) => Object.values(sourceItems || {})
            .filter((item) => isRangeItem(item))
            .map((item) => {
                const min = item.VALUES.MIN;
                const max = item.VALUES.MAX;
                const boundMin = toNumber(min.VALUE);
                const boundMax = toNumber(max.VALUE);

                return {
                    code: item.CODE,
                    name: item.NAME,
                    isPrice: Boolean(item.PRICE),
                    currency: min.CURRENCY || '',
                    decimals: Number(item.DECIMALS || 0),
                    minName: min.CONTROL_NAME,
                    maxName: max.CONTROL_NAME,
                    boundMin: boundMin,
                    boundMax: boundMax,
                    lo: isFilled(min.HTML_VALUE) ? toNumber(min.HTML_VALUE) : boundMin,
                    hi: isFilled(max.HTML_VALUE) ? toNumber(max.HTML_VALUE) : boundMax
                };
            });

        const buildSelection = (sourceItems) => {
            const selection = {};

            Object.values(sourceItems || {}).forEach((item) => {
                const values = item.VALUES || {};

                if (isRangeItem(item)) {
                    if (isFilled(values.MIN.HTML_VALUE)) {
                        selection[values.MIN.CONTROL_NAME] = String(values.MIN.HTML_VALUE);
                    }
                    if (isFilled(values.MAX.HTML_VALUE)) {
                        selection[values.MAX.CONTROL_NAME] = String(values.MAX.HTML_VALUE);
                    }
                    return;
                }

                Object.values(values).forEach((value) => {
                    if (value.CHECKED) {
                        selection[value.CONTROL_NAME] = isFilled(value.HTML_VALUE) ? value.HTML_VALUE : 'Y';
                    }
                });
            });

            return selection;
        };

        const items = computed(() => buildItems());
        const ranges = signal(buildRanges(data.value.ITEMS));
        const selection = signal(buildSelection(data.value.ITEMS));
        const count = signal(isFilled(source.ELEMENT_COUNT) ? Number(source.ELEMENT_COUNT) : null);
        const isLoading = signal(false);

        const isChecked = (controlName) => Object.prototype.hasOwnProperty.call(selection.value, controlName);

        const baseUrl = () => data.value.FORM_ACTION || window.location.pathname;

        const buildQuery = () => {
            const params = new URLSearchParams();

            params.set('set_filter', 'y');

            Object.entries(selection.value).forEach(([name, value]) => {
                params.set(name, value);
            });

            ranges.value.forEach((range) => {
                if (range.lo > range.boundMin) {
                    params.set(range.minName, String(range.lo));
                }
                if (range.hi < range.boundMax) {
                    params.set(range.maxName, String(range.hi));
                }
            });

            return params;
        };

        const url = () => {
            const base = baseUrl();
            const separator = base.indexOf('?') > -1 ? '&' : '?';

            return base + separator + buildQuery().toString();
        };

        // ----------------------------------------------------------- счётчики
        const applyCounts = (result) => {
            if (!result || !result.ITEMS) {
                return;
            }

            if (isFilled(result.ELEMENT_COUNT)) {
                count.value = Number(result.ELEMENT_COUNT);
            }

            const nextItems = Object.assign({}, data.value.ITEMS);

            Object.keys(nextItems).forEach((id) => {
                const freshItem = result.ITEMS[id];

                if (!freshItem || !freshItem.VALUES) {
                    return;
                }

                const values = {};

                Object.keys(nextItems[id].VALUES || {}).forEach((key) => {
                    const value = nextItems[id].VALUES[key];
                    const freshValue = freshItem.VALUES[key];

                    values[key] = freshValue && isFilled(freshValue.ELEMENT_COUNT)
                        ? Object.assign({}, value, { ELEMENT_COUNT: freshValue.ELEMENT_COUNT })
                        : value;
                });

                nextItems[id] = Object.assign({}, nextItems[id], { VALUES: values });
            });

            data.value = Object.assign({}, data.value, { ITEMS: nextItems });
        };

        let countsTimer = 0;

        const fetchCounts = () => {
            window.clearTimeout(countsTimer);

            countsTimer = window.setTimeout(() => {
                const params = buildQuery();
                const base = baseUrl();
                const separator = base.indexOf('?') > -1 ? '&' : '?';

                params.set('ajax', 'y');
                isLoading.value = true;

                fetch(base + separator + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then((response) => response.json())
                    .then((payload) => applyCounts(payload.result || payload))
                    .catch((error) => {
                        console.warn('[smart-filter] не удалось получить счётчики:', error);
                    })
                    .finally(() => {
                        isLoading.value = false;
                    });
            }, 300);
        };

        // ----------------------------------------------------------- actions
        const setSelection = (controlName, value) => {
            selection.value = Object.assign({}, selection.value, { [controlName]: value });
        };

        const clearSelection = (controlName) => {
            const next = Object.assign({}, selection.value);

            delete next[controlName];
            selection.value = next;
        };

        const toggle = (controlName) => {
            if (isChecked(controlName)) {
                clearSelection(controlName);
            } else {
                setSelection(controlName, 'Y');
            }

            fetchCounts();
        };

        const clearGroup = (item) => {
            const next = Object.assign({}, selection.value);

            Object.values(item.VALUES || {}).forEach((value) => {
                delete next[value.CONTROL_NAME];
            });

            selection.value = next;
            fetchCounts();
        };

        const selectSingle = (item, controlName) => {
            const next = Object.assign({}, selection.value);

            Object.values(item.VALUES || {}).forEach((value) => {
                delete next[value.CONTROL_NAME];
            });

            if (controlName) {
                next[controlName] = 'Y';
            }

            selection.value = next;
            fetchCounts();
        };

        const setRangeValue = (code, handle, value) => {
            const index = ranges.value.findIndex((range) => range.code === code);

            if (index < 0) {
                return;
            }

            const range = ranges.value[index];
            const next = Object.assign({}, range);

            if (handle === 0) {
                next.lo = Math.min(value, next.hi);
            } else {
                next.hi = Math.max(value, next.lo);
            }

            if (next.lo === range.lo && next.hi === range.hi) {
                return;
            }

            const list = ranges.value.slice();

            list[index] = next;
            ranges.value = list;
        };

        const resetRange = (code) => {
            const index = ranges.value.findIndex((range) => range.code === code);

            if (index < 0) {
                return;
            }

            const list = ranges.value.slice();

            list[index] = Object.assign({}, list[index], {
                lo: list[index].boundMin,
                hi: list[index].boundMax
            });
            ranges.value = list;
            fetchCounts();
        };

        const selected = computed(() => {
            const result = [];

            items.value.forEach((item) => {
                Object.values(item.VALUES || {}).forEach((value) => {
                    if (isChecked(value.CONTROL_NAME)) {
                        result.push({
                            kind: 'value',
                            key: value.CONTROL_NAME,
                            groupName: item.NAME,
                            text: value.VALUE
                        });
                    }
                });
            });

            ranges.value.forEach((range) => {
                if (range.lo > range.boundMin || range.hi < range.boundMax) {
                    result.push({
                        kind: 'range',
                        key: range.code,
                        groupName: range.name,
                        text: formatMoney(range.lo, range.currency, range.decimals)
                            + ' — ' + formatMoney(range.hi, range.currency, range.decimals)
                    });
                }
            });

            return result;
        });

        const removeChip = (chip) => {
            if (chip.kind === 'range') {
                resetRange(chip.key);
                return;
            }

            clearSelection(chip.key);
            fetchCounts();
        };

        const apply = () => {
            window.location.href = url();
        };

        const reset = () => {
            selection.value = {};
            ranges.value = buildRanges(data.value.ITEMS);
            window.location.href = baseUrl();
        };

        return {
            data,
            items,
            ranges,
            selection,
            selected,
            count,
            isLoading,
            displayTypes: DISPLAY_TYPES,
            isChecked,
            toggle,
            selectSingle,
            clearGroup,
            setRangeValue,
            resetRange,
            removeChip,
            buildQuery,
            url,
            apply,
            reset,
            fetchCounts
        };
    });

    // ------------------------------------------------------------- component
    signa.component('smart-filter', ({ html, effect }, states) => {
        const filter = states.smartFilter;
        const sliderNodes = new Map();
        const hasSlider = typeof window.noUiSlider !== 'undefined';

        const getSliderNode = (range) => {
            if (!sliderNodes.has(range.code)) {
                const node = document.createElement('div');

                node.className = 'smart-filter__slider';
                sliderNodes.set(range.code, node);
            }

            return sliderNodes.get(range.code);
        };

        const initSliders = () => {
            if (!hasSlider) {
                return;
            }

            filter.ranges.value.forEach((range) => {
                const node = sliderNodes.get(range.code);

                if (!node || !node.isConnected || node.noUiSlider || range.boundMax <= range.boundMin) {
                    return;
                }

                const tooltip = {
                    to: (value) => formatMoney(value, range.currency, range.decimals)
                };

                try {
                    window.noUiSlider.create(node, {
                        start: [range.lo, range.hi],
                        connect: true,
                        step: range.decimals > 0 ? 1 / Math.pow(10, range.decimals) : 1,
                        range: { min: range.boundMin, max: range.boundMax },
                        tooltips: [tooltip, tooltip]
                    });
                } catch (error) {
                    console.error('[smart-filter] не удалось создать ползунок:', error);
                    return;
                }

                node.noUiSlider.on('update', (values, handle) => {
                    filter.setRangeValue(range.code, handle, toNumber(values[handle]));
                });

                node.noUiSlider.on('change', () => filter.fetchCounts());
            });
        };

        // ползунки создаются после рендера, значения синхронизируем со стейтом
        effect(() => {
            const ranges = filter.ranges.value;

            window.requestAnimationFrame(() => {
                initSliders();

                ranges.forEach((range) => {
                    const node = sliderNodes.get(range.code);

                    if (!node || !node.noUiSlider) {
                        return;
                    }

                    const values = node.noUiSlider.get().map(toNumber);

                    if (Math.abs(values[0] - range.lo) < 0.001 && Math.abs(values[1] - range.hi) < 0.001) {
                        return;
                    }

                    node.noUiSlider.set([range.lo, range.hi]);
                });
            });
        });

        const renderCounter = (value) => (isFilled(value.ELEMENT_COUNT)
            ? html`<span class="smart-filter__count">${value.ELEMENT_COUNT}</span>`
            : null);

        const renderCheckboxes = (item) => html`
            <div class="d-flex flex-column gap-2">
                ${Object.values(item.VALUES || {}).map((value) => html`
                    <div class="form-check mb-0">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id=${'sf-' + value.CONTROL_ID}
                            .checked=${filter.isChecked(value.CONTROL_NAME)}
                            onchange=${() => filter.toggle(value.CONTROL_NAME)}
                        >
                        <label class="form-check-label d-flex align-items-center gap-1" for=${'sf-' + value.CONTROL_ID}>
                            ${isFilled(value.PICT)
                                ? html`<img class="smart-filter__pict rounded" src=${value.PICT} alt="" loading="lazy">`
                                : null}
                            <span>${value.VALUE}</span>
                            ${renderCounter(value)}
                        </label>
                    </div>
                `)}
            </div>
        `;

        const renderRadio = (item) => html`
            <div class="d-flex flex-column gap-2">
                ${Object.values(item.VALUES || {}).map((value) => html`
                    <div class="form-check mb-0">
                        <input
                            class="form-check-input"
                            type="radio"
                            name=${'sf-' + item.CODE}
                            id=${'sf-' + value.CONTROL_ID}
                            .checked=${filter.isChecked(value.CONTROL_NAME)}
                            onchange=${() => filter.selectSingle(item, value.CONTROL_NAME)}
                        >
                        <label class="form-check-label d-flex align-items-center gap-1" for=${'sf-' + value.CONTROL_ID}>
                            <span>${value.VALUE}</span>
                            ${renderCounter(value)}
                        </label>
                    </div>
                `)}
            </div>
        `;

        const renderDropdown = (item) => html`
            <select
                class="form-select form-select-sm"
                onchange=${(event) => filter.selectSingle(item, event.target.value)}
            >
                <option value="">Все</option>
                ${Object.values(item.VALUES || {}).map((value) => html`
                    <option value=${value.CONTROL_NAME} .selected=${filter.isChecked(value.CONTROL_NAME)}>
                        ${value.VALUE}${isFilled(value.ELEMENT_COUNT) ? ' (' + value.ELEMENT_COUNT + ')' : ''}
                    </option>
                `)}
            </select>
        `;

        const renderGroup = (item) => {
            const type = item.DISPLAY_TYPE;
            let content = renderCheckboxes(item);

            if (type === DISPLAY_TYPES.DROPDOWN || type === DISPLAY_TYPES.DROPDOWN_WITH_PICTURES_AND_LABELS) {
                content = renderDropdown(item);
            } else if (type === DISPLAY_TYPES.RADIO_BUTTONS) {
                content = renderRadio(item);
            }

            return html`
                <div class="smart-filter__group mb-4">
                    <div class="smart-filter__group-title fw-semibold mb-2">${item.NAME}</div>
                    ${content}
                </div>
            `;
        };

        const renderRange = (range) => {
            const useSlider = hasSlider && range.boundMax > range.boundMin;

            return html`
                <div class="smart-filter__group mb-4">
                    <div class="smart-filter__group-title fw-semibold mb-2">${range.name}</div>

                    ${useSlider
                        ? html`<div class="px-2 pt-1">${getSliderNode(range)}</div>`
                        : html`
                            <div class="d-flex align-items-center gap-2">
                                <input
                                    class="form-control form-control-sm"
                                    type="number"
                                    .value=${String(range.lo)}
                                    onchange=${(event) => filter.setRangeValue(range.code, 0, toNumber(event.target.value))}
                                >
                                <span class="text-muted">—</span>
                                <input
                                    class="form-control form-control-sm"
                                    type="number"
                                    .value=${String(range.hi)}
                                    onchange=${(event) => filter.setRangeValue(range.code, 1, toNumber(event.target.value))}
                                >
                            </div>
                        `}
                </div>
            `;
        };

        const renderSelected = () => html`
            <div class="smart-filter__selected d-flex flex-wrap gap-2 mb-3">
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

        return () => html`
            <div class=${'smart-filter card border-0 shadow-sm' + (filter.isLoading.value ? ' is-loading' : '')}>
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                    <span class="fw-semibold">Подбор параметров</span>
                    ${filter.selected.value.length > 0
                        ? html`<button
                            type="button"
                            class="btn btn-link btn-sm p-0 text-decoration-none"
                            onclick=${() => filter.reset()}
                        >Сбросить</button>`
                        : null}
                </div>

                <div class="card-body">
                    ${filter.selected.value.length > 0 ? renderSelected() : null}
                    ${filter.items.value.map((item) => renderGroup(item))}
                    ${filter.ranges.value.map((range) => renderRange(range))}

                    <button type="button" class="btn btn-primary w-100 mt-2" onclick=${() => filter.apply()}>
                        Показать${filter.count.value !== null ? ' (' + filter.count.value + ')' : ''}
                    </button>
                </div>
            </div>
        `;
    });
})();
