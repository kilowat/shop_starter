/**
 * Сортировка каталога: при смене select уходим GET-переходом на ту же
 * страницу с новым sort, сохраняя параметры фильтра и сбрасывая пагинацию.
 */
signa.component('catalog-sort', ({ $this, effect }) => {
    const select = $this.querySelector('select');

    if (!select) {
        return;
    }

    effect(() => {
        const onChange = () => {
            const url = new URL(window.location.href);

            url.searchParams.set(select.name || 'sort', select.value);

            [...url.searchParams.keys()].forEach((name) => {
                if (/^PAGEN_\d+$/.test(name)) {
                    url.searchParams.delete(name);
                }
            });

            window.location.assign(url.toString());
        };

        select.addEventListener('change', onChange);

        return () => select.removeEventListener('change', onChange);
    });
});