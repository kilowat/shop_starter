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

    /**
     * При применении фильтра каталог всегда открывается с первой страницы,
     * поэтому из URL просто выбрасываем любые параметры пагинации (PAGEN_1, PAGEN_2, …).
     */
    const PAGE_PARAMS = /^PAGEN_/;

    /** Изменения для signa.location.set: все PAGEN_* -> null (удалить из URL). */
    const excludePageParams = (query) => Object.fromEntries(
        Object.keys(query || {})
            .filter((key) => PAGE_PARAMS.test(key))
            .map((key) => [key, null])
    );

    // --------------------------------------------------------------- store
    signa.state('smartFilter', ({ signal, computed, effect }) => {
        const source = window.__SMART_FILTER__ || {};

        const items = Object.values(source.ITEMS || {}).filter(
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

        const ranges = signal(buildRanges(source.ITEMS));
        const selection = signal(buildSelection(source.ITEMS));

        // { CONTROL_NAME: число товаров } — обновляется живыми запросами
        const counts = signal(buildCounts(source.ITEMS));
        const total = signal(Number(source.TOTAL || 0));
        const isLoading = signal(false);

        const isChecked = (controlName) => Object.prototype.hasOwnProperty.call(selection.value, controlName);

        // CONTROL_NAME -> подпись значения и группа (для чипов)
        const labels = {};

        items.forEach((item) => {
            Object.values(item.VALUES || {}).forEach((value) => {
                labels[value.CONTROL_NAME] = {
                    groupName: item.NAME,
                    text: value.VALUE
                };
            });
        });
        const counter = (controlName) => counts.value[controlName];

        /**
         * Параметры фильтра из черновика: значение или null (снять).
         * null нужен, чтобы «Применить» умело убирать снятые значения из URL.
         */
        const draftParams = () => {
            const params = {};

            items.forEach((item) => {
                Object.values(item.VALUES || {}).forEach((value) => {
                    params[value.CONTROL_NAME] = isChecked(value.CONTROL_NAME) ? 'Y' : null;
                });
            });

            ranges.value.forEach((range) => {
                params[range.minName] = range.lo > range.boundMin ? String(range.lo) : null;
                params[range.maxName] = range.hi < range.boundMax ? String(range.hi) : null;
            });

            const hasFilter = Object.keys(params).some((name) => params[name] !== null);

            params.set_filter = hasFilter ? 'y' : null;

            return params;
        };

        // ----------------------------------------------------------- счётчики
        /** Применить данные из arResult: счётчики по значениям и общее число товаров. */
        const applyFilterData = (result) => {
            if (!result) {
                return;
            }

            counts.value = result.COUNTS || {};
            total.value = Number(result.TOTAL || 0);
        };

        function buildCounts(sourceItems) {
            const result = {};

            Object.values(sourceItems || {}).forEach((item) => {
                Object.values(item.VALUES || {}).forEach((value) => {
                    if (value.CONTROL_NAME) {
                        result[value.CONTROL_NAME] = Number(value.ELEMENT_COUNT || 0);
                    }
                });
            });

            return result;
        }


        let countsTimer = 0;
        let countsRequest = 0;

        /**
         * Живые счётчики: считаем по черновику (что натыкано сейчас, а не что применено).
         */
        const refreshCounts = () => {
            window.clearTimeout(countsTimer);

            countsTimer = window.setTimeout(async () => {
                const search = new URLSearchParams();

                Object.entries(draftParams()).forEach(([name, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        search.set(name, value);
                    }
                });

                const query = search.toString();
                const url = window.location.pathname + (query ? '?' + query : '');
                const id = ++countsRequest;

                // фильтр читает значения из query, action и sessid — в теле
                const body = new URLSearchParams({
                    sessid: document.getElementById('catalog-sessid')?.value || '',
                    catalog_action: 'filter'
                });

                isLoading.value = true;

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        body,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const payload = await response.json();

                    if (id !== countsRequest || !payload || payload.status !== 'success') {
                        return;
                    }

                    applyFilterData(payload.data.result);
                } catch (error) {
                    console.warn('[smart-filter] не удалось получить счётчики:', error);
                } finally {
                    if (id === countsRequest) {
                        isLoading.value = false;
                    }
                }
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

            refreshCounts();
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
            refreshCounts();
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
            refreshCounts();
        };

        // URL изменился (применили фильтр, back/forward) -> черновик подстраивается под него
        effect(() => {
            const query = signa.location.query.value || {};
            const nextSelection = {};

            // сначала сохраняем порядок, в котором пользователь заполнял фильтр
            Object.keys(selection.peek()).forEach((controlName) => {
                if (query[controlName] !== undefined) {
                    nextSelection[controlName] = query[controlName];
                }
            });

            // остальное (back/forward, ссылка с фильтром) — в порядке разметки
            items.forEach((item) => {
                Object.values(item.VALUES || {}).forEach((value) => {
                    if (query[value.CONTROL_NAME] !== undefined && !(value.CONTROL_NAME in nextSelection)) {
                        nextSelection[value.CONTROL_NAME] = query[value.CONTROL_NAME];
                    }
                });
            });

            selection.value = nextSelection;
            ranges.value = ranges.peek().map((range) => Object.assign({}, range, {
                lo: query[range.minName] !== undefined ? toNumber(query[range.minName]) : range.boundMin,
                hi: query[range.maxName] !== undefined ? toNumber(query[range.maxName]) : range.boundMax
            }));
        });

        const selected = computed(() => {
            // порядок — как пользователь заполнял фильтр (порядок ключей в selection)
            const result = Object.keys(selection.value).map((controlName) => {
                const label = labels[controlName] || { groupName: '', text: controlName };

                return {
                    kind: 'value',
                    key: controlName,
                    groupName: label.groupName,
                    text: label.text
                };
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

        /**
         * Крестик на чипе: убираем значение и сразу применяем фильтр,
         * но без скролла страницы.
         */
        const removeChip = (chip) => {
            if (chip.kind === 'range') {
                const index = ranges.value.findIndex((range) => range.code === chip.key);

                if (index < 0) {
                    return;
                }

                const list = ranges.value.slice();

                list[index] = Object.assign({}, list[index], {
                    lo: list[index].boundMin,
                    hi: list[index].boundMax
                });
                ranges.value = list;
            } else {
                clearSelection(chip.key);
            }

            apply(false);
        };

        /**
         * Применить фильтр: пишем черновик в URL. Список, сортировка и пагинация
         * обновятся сами — их слушает catalogState.
         */
        const apply = (withScroll = true) => {
            signa.location.set(Object.assign(
                draftParams(),
                excludePageParams(signa.location.query.value)
            ));

            if (withScroll) {
                document.querySelector('smart-filter')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        };

        /**
         * Сбросить фильтр: снимаем все arrFilter-параметры и возвращаемся на первую страницу.
         */
        const reset = () => {
            const params = { set_filter: null };

            items.forEach((item) => {
                Object.values(item.VALUES || {}).forEach((value) => {
                    params[value.CONTROL_NAME] = null;
                });
            });

            ranges.peek().forEach((range) => {
                params[range.minName] = null;
                params[range.maxName] = null;
            });

            const selectionBefore = selection.value;

            selection.value = {};
            ranges.value = ranges.value.map((range) => Object.assign({}, range, {
                lo: range.boundMin,
                hi: range.boundMax
            }));

            signa.location.set(Object.assign(params, excludePageParams(signa.location.query.value)));

            // URL мог не измениться (фильтр не был применён) — пересчитываем счётчики
            if (Object.keys(selectionBefore).length === 0) {
                refreshCounts();
            }
        };

        return {
            items,
            ranges,
            selection,
            selected,
            counts,
            total,
            counter,
            isLoading,
            displayTypes: DISPLAY_TYPES,
            isChecked,
            toggle,
            selectSingle,
            setRangeValue,
            resetRange,
            removeChip,
            draftParams,
            apply,
            reset,
            refreshCounts
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

                node.noUiSlider.on('change', () => filter.refreshCounts());
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

        const renderCounter = (value) => {
            const number = filter.counter(value.CONTROL_NAME);

            return isFilled(number) ? html`<span class="smart-filter__count">${number}</span>` : null;
        };

        const isDropdown = (item) => (
            item.DISPLAY_TYPE === DISPLAY_TYPES.DROPDOWN
            || item.DISPLAY_TYPE === DISPLAY_TYPES.DROPDOWN_WITH_PICTURES_AND_LABELS
        );

        const renderChoices = (item, multiple) => html`
            <div class="d-flex flex-column gap-2">
                ${Object.values(item.VALUES || {}).map((value) => html`
                    <div class="form-check mb-0">
                        <input
                            class="form-check-input"
                            type=${multiple ? 'checkbox' : 'radio'}
                            name=${'sf-' + item.CODE}
                            id=${'sf-' + value.CONTROL_ID}
                            .checked=${filter.isChecked(value.CONTROL_NAME)}
                            onchange=${() => (multiple
                                ? filter.toggle(value.CONTROL_NAME)
                                : filter.selectSingle(item, value.CONTROL_NAME))}
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

        const renderDropdown = (item) => html`
            <select class="form-select form-select-sm" onchange=${(event) => filter.selectSingle(item, event.target.value)}>
                <option value="">Все</option>
                ${Object.values(item.VALUES || {}).map((value) => html`
                    <option value=${value.CONTROL_NAME} .selected=${filter.isChecked(value.CONTROL_NAME)}>
                        ${value.VALUE}${isFilled(filter.counter(value.CONTROL_NAME))
                            ? ' (' + filter.counter(value.CONTROL_NAME) + ')'
                            : ''}
                    </option>
                `)}
            </select>
        `;

        const renderGroup = (item) => html`
            <div class="smart-filter__group mb-4">
                <div class="smart-filter__group-title fw-semibold mb-2">${item.NAME}</div>
                ${isDropdown(item)
                    ? renderDropdown(item)
                    : renderChoices(item, item.DISPLAY_TYPE !== DISPLAY_TYPES.RADIO_BUTTONS)}
            </div>
        `;

        const renderRange = (range) => html`
            <div class="smart-filter__group mb-4">
                <div class="smart-filter__group-title fw-semibold mb-2">${range.name}</div>
                <div class="px-2 pt-1">${getSliderNode(range)}</div>
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
                    ${filter.items.map((item) => renderGroup(item))}
                    ${filter.ranges.value.map((range) => renderRange(range))}

                    <button type="button" class="btn btn-primary w-100 mt-2" onclick=${() => filter.apply()}>
                        Показать${filter.total.value ? ' (' + filter.total.value + ')' : ''}
                    </button>
                </div>
            </div>
        `;
    });
})();
