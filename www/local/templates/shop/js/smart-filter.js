/**
 * Умный фильтр: вёрстку рисует PHP, компонент её не трогает.
 *
 * Чекбоксы и ползунки меняют состояние формы; после каждого изменения
 * уходит AJAX (ajax=y), смарт-фильтр возвращает только числа, и они
 * записываются в готовые узлы [data-count].
 * Кнопка «Применить» — обычный submit формы на текущую страницу.
 */
signa.component('smart-filter', ({ $this, effect, signal }) => {
    const form = $this.querySelector('form');

    if (!form) {
        return;
    }

    const counts = signal({});
    let request = 0;

    // Пишем только текст счётчиков: ни один узел вёрстки не пересоздаётся.
    effect(() => {
        const values = counts.value;

        $this.querySelectorAll('[data-control]').forEach((item) => {
            const key = item.dataset.control;
            const counter = item.querySelector('.smart-filter__count');

            if (counter && key in values) {
                counter.dataset.count = values[key];
            }
        });
    });

    /** Пересчитать счётчики по текущему состоянию формы. */
    const refresh = async () => {
        const id = ++request;
        const url = new URL(form.action, location.href);

        url.search = '';

        new FormData(form).forEach((value, name) => {
            if (value !== '') {
                url.searchParams.set(name, value);
            }
        });

        url.searchParams.set('ajax', 'y');

        try {
            const response = await fetch(url, { cache: 'no-store' });
            const data = await response.json();

            if (id === request) {
                counts.value = data || {};
            }
        } catch (error) {
            console.warn('[smart-filter] не удалось обновить счётчики:', error);
        }
    };

    /** Ползунок пишет значения в поля диапазона, чтобы они ушли с формой. */
    const initSlider = (node) => {
        if (!window.noUiSlider || node.noUiSlider) {
            return;
        }

        const min = Number(node.dataset.min);
        const max = Number(node.dataset.max);

        if (!(max > min)) {
            return;
        }

        const decimals = Number(node.dataset.decimals) || 0;
        const round = (value) => Number(Number(value).toFixed(decimals));
        const group = node.closest('.smart-filter__group');
        const minInput = group.querySelector('[data-side="min"]');
        const maxInput = group.querySelector('[data-side="max"]');

        try {
            window.noUiSlider.create(node, {
                start: [Number(node.dataset.lo), Number(node.dataset.hi)],
                connect: true,
                step: decimals > 0 ? 1 / 10 ** decimals : 1,
                range: { min, max },
            });
        } catch (error) {
            console.warn('[smart-filter] не удалось создать ползунок:', error);
            return;
        }

        // Границы по умолчанию в поля не пишем — иначе они всегда попадут в URL.
        node.noUiSlider.on('update', (values) => {
            minInput.value = Number(values[0]) > min ? round(values[0]) : '';
            maxInput.value = Number(values[1]) < max ? round(values[1]) : '';
        });

        // Ползунок отпущен — пересчитываем счётчики, применяется по кнопке.
        node.noUiSlider.on('change', refresh);
    };

    const initSliders = () => $this.querySelectorAll('[data-slider]').forEach(initSlider);

    initSliders();

    // noUiSlider подключается в component_epilog шаблона — если он ещё не
    // загрузился, инициализируем ползунки после разбора страницы.
    if (!window.noUiSlider) {
        document.addEventListener('DOMContentLoaded', initSliders, { once: true });
        window.addEventListener('load', initSliders, { once: true });
    }

    form.addEventListener('change', refresh);
});
