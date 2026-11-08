/**
 * Умный фильтр: JS отвечает только за данные, разметку рисует PHP
 * (components/bitrix/catalog.smart.filter/signa/template.php).
 *
 * Что делает:
 *   - форма -> URL (список перезагружает catalog.js);
 *   - чекбоксы, радио и селекты применяются сразу при каждом изменении;
 *   - после изменения — AJAX-запрос счётчиков и запись чисел в [data-count];
 *   - ползунок noUiSlider -> поля диапазона;
 *   - сброс фильтра.
 *
 * Ни одного html-шаблона здесь нет. Скрипт подключается в <head>,
 * поэтому стартуем после разбора разметки.
 */
(() => {
    'use strict';

    // =============================== НАСТРОЙКИ ===============================
    const CONFIG = {
        // Применять фильтр сразу при изменении чекбоксов/радио/селектов.
        // false — применение только по кнопке (submit), как раньше.
        autoApply: true,

        // Задержка (мс) перед применением фильтра после изменения.
        // Склеивает серию быстрых кликов в одно обновление списка. 0 — без задержки.
        applyDelay: 250,

        // Задержка (мс) перед запросом счётчиков после изменения.
        countsDelay: 50,
    };
    // =========================================================================

    const start = () => {
        const signa = window.signa;
        const root = document.querySelector('[data-filter]');

        if (!signa || !root) {
            return;
        }

        const form = root.querySelector('[data-filter-form]');

        if (!form) {
            return;
        }

        const PAGE_PARAMS = /^PAGEN_/;

        const escapeName = (value) => (
            window.CSS && typeof window.CSS.escape === 'function' ? window.CSS.escape(value) : value
        );

        const setLoading = (state) => root.classList.toggle('is-loading', Boolean(state));

        /** Поля формы -> параметры URL (пустые и выключенные не попадают). */
        const formParams = () => {
            const params = {};
            let hasFilter = false;

            form.querySelectorAll('input[name], select[name]').forEach((node) => {
                if (node.disabled || node.name === '' || node.name === 'set_filter') {
                    return;
                }

                if (node.type === 'checkbox' || node.type === 'radio') {
                    if (!node.checked) {
                        return;
                    }

                    params[node.name] = node.value || 'Y';
                } else {
                    if (node.value === '') {
                        return;
                    }

                    params[node.name] = node.value;
                }

                hasFilter = true;
            });

            params.set_filter = hasFilter ? 'y' : null;

            return params;
        };

        const toQuery = (params) => {
            const search = new URLSearchParams();

            Object.entries(params).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    search.set(key, value);
                }
            });

            return search.toString();
        };

        /** Снять с URL все параметры фильтра и пагинации. */
        const clearParams = () => {
            const params = {};

            Object.keys(signa.location.query.value).forEach((key) => {
                if (key.startsWith('arrFilter') || PAGE_PARAMS.test(key) || key === 'set_filter') {
                    params[key] = null;
                }
            });

            return params;
        };

        // ----------------------------------------------------------- счётчики
        let countsTimer = 0;
        let countsRequest = 0;

        const applyCounts = (result) => {
            if (!result) {
                return;
            }

            const counts = result.COUNTS || {};

            root.querySelectorAll('[data-count]').forEach((node) => {
                const name = node.dataset.count;

                if (!Object.prototype.hasOwnProperty.call(counts, name)) {
                    return;
                }

                const count = Number(counts[name]) || 0;
                node.textContent = String(count);

                // checkbox с тем же name (hidden-поля групп сюда не попадают)
                form.querySelectorAll(`input[type="checkbox"][data-value="${escapeName(name)}"]`).forEach((input) => {
                    input.disabled = count === 0 && !input.checked;
                });
            });

            const total = root.querySelector('[data-filter-total]');

            if (total) {
                const value = Number(result.TOTAL) || 0;
                total.textContent = value > 0 ? ` (${value})` : '';
            }
        };

        /**
         * Пересчитать счётчики по текущему состоянию формы.
         */
        const refreshCounts = (delay = CONFIG.countsDelay) => {
            window.clearTimeout(countsTimer);

            countsTimer = window.setTimeout(async () => {
                const query = toQuery(formParams());
                const url = window.location.pathname + (query ? '?' + query : '');
                const id = ++countsRequest;
                const body = new URLSearchParams({
                    sessid: window.__BITRIX_SESSID__ || '',
                    ajax_action: 'filter',
                });

                setLoading(true);

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        body,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const payload = await response.json();

                    if (id !== countsRequest || !payload || payload.status !== 'success') {
                        return;
                    }

                    applyCounts(payload.data && payload.data.result);
                } catch (error) {
                    console.warn('[filter] не удалось обновить счётчики:', error);
                } finally {
                    if (id === countsRequest) {
                        setLoading(false);
                    }
                }
            }, delay);
        };

        // ---------------------------------------------------------- ползунок
        const formatMoney = (value, currency, decimals) => {
            try {
                return new Intl.NumberFormat('ru-RU', {
                    style: currency ? 'currency' : 'decimal',
                    currency: currency || undefined,
                    maximumFractionDigits: decimals || 0,
                    minimumFractionDigits: 0,
                }).format(value);
            } catch (error) {
                return String(value);
            }
        };

        const initSliders = () => {
            if (typeof window.noUiSlider === 'undefined') {
                return;
            }

            root.querySelectorAll('[data-slider]').forEach((node) => {
                if (node.noUiSlider) {
                    return;
                }

                const min = Number(node.dataset.min);
                const max = Number(node.dataset.max);

                if (!(max > min)) {
                    return;
                }

                const decimals = Number(node.dataset.decimals || 0);
                const currency = node.dataset.currency || '';
                const range = node.closest('[data-range]');
                const minInput = range ? range.querySelector('input[data-side="min"]') : null;
                const maxInput = range ? range.querySelector('input[data-side="max"]') : null;
                const format = (value) => (
                    decimals > 0 ? String(Number(value.toFixed(decimals))) : String(Math.round(value))
                );
                const tooltip = { to: (value) => formatMoney(value, currency, decimals) };

                try {
                    window.noUiSlider.create(node, {
                        start: [Number(node.dataset.lo), Number(node.dataset.hi)],
                        connect: true,
                        step: decimals > 0 ? 1 / Math.pow(10, decimals) : 1,
                        range: { min, max },
                        tooltips: [tooltip, tooltip],
                    });
                } catch (error) {
                    console.error('[filter] не удалось создать ползунок:', error);
                    return;
                }

                // границы по умолчанию не пишем в поля — иначе они попадут в URL
                node.noUiSlider.on('update', (values) => {
                    if (minInput) {
                        minInput.value = Number(values[0]) > min ? format(Number(values[0])) : '';
                    }

                    if (maxInput) {
                        maxInput.value = Number(values[1]) < max ? format(Number(values[1])) : '';
                    }
                });

                // ползунок отпущен — только пересчёт счётчиков, применяется по submit
                node.noUiSlider.on('change', () => refreshCounts(0));
            });
        };

        // ---------------------------------------------------------- действия
        let applyTimer = 0;

        /**
         * Применить форму: состояние уходит в URL, список перезагружает catalog.js,
         * он же прокручивает страницу к началу каталога.
         */
        const apply = () => {
            window.clearTimeout(applyTimer);
            signa.location.set(Object.assign(clearParams(), formParams()));
            refreshCounts(0);
        };

        /** Применить с небольшой задержкой — склеивает серию быстрых кликов. */
        const scheduleApply = (delay = CONFIG.applyDelay) => {
            window.clearTimeout(applyTimer);
            applyTimer = window.setTimeout(apply, delay);
        };

        const reset = () => {
            form.querySelectorAll('input[type="checkbox"], input[type="radio"]').forEach((input) => {
                input.checked = false;
                input.disabled = false;
            });

            form.querySelectorAll('input[type="hidden"][data-group]').forEach((input) => {
                input.disabled = true;
            });

            form.querySelectorAll('input[type="number"]').forEach((input) => {
                input.value = '';
            });

            form.querySelectorAll('select[data-single-select]').forEach((select) => {
                select.value = '';
            });

            root.querySelectorAll('[data-slider]').forEach((node) => {
                if (!node.noUiSlider) {
                    return;
                }

                node.noUiSlider.set([Number(node.dataset.min), Number(node.dataset.max)]);
            });

            apply();
        };

        /**
         * Радио и селекты групп с одиночным выбором управляют скрытыми полями:
         * у каждого значения свой name (arrFilter_*), поэтому включён только один.
         */
        const syncSingle = (node) => {
            const code = node.dataset.radio || node.dataset.singleSelect;

            if (!code) {
                return;
            }

            const selected = node.value || '';

            form.querySelectorAll(`input[type="hidden"][data-group="${escapeName(code)}"]`).forEach((input) => {
                input.disabled = input.name !== selected;
            });
        };

        /** «Сбросить» показываем, только когда что-то выбрано. */
        const syncReset = () => {
            const resetButton = root.querySelector('[data-filter-reset]');

            if (!resetButton) {
                return;
            }

            const hasSelection = form.querySelectorAll('input[type="checkbox"]:checked, input[type="radio"]:checked').length > 0
                || Array.from(form.querySelectorAll('input[type="number"]')).some((input) => input.value !== '')
                || form.querySelectorAll('input[type="hidden"][data-group]:not([disabled])').length > 0;

            resetButton.classList.toggle('d-none', !hasSelection);
        };

        form.addEventListener('change', (event) => {
            const target = event.target;

            syncSingle(target);
            syncReset();

            if (CONFIG.autoApply && target.matches('input[type="checkbox"], input[type="radio"], select')) {
                // чекбокс/радио/селект: применяем фильтр сразу
                // (apply сам обновит счётчики)
                scheduleApply();
            } else {
                // числовые поля: только пересчёт счётчиков, применяется по submit
                refreshCounts();
            }
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            apply();
        });

        const resetButton = root.querySelector('[data-filter-reset]');

        if (resetButton) {
            resetButton.addEventListener('click', () => {
                reset();
                syncReset();
            });
        }

        initSliders();
        syncReset();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();