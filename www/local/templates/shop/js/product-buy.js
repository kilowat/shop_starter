signa.component('product-buy', ({ signal, computed, html, prop, effect, $this }, { basket }) => {
    const productId = Number(prop('productId').value || 0);
    const detailUrl = String(prop('url').value || '');
    const offerSelectId = String(prop('offerSelect').value || '');
    const offerSelect = offerSelectId ? document.getElementById(offerSelectId) : null;
    const selectedId = signal(productId);
    const available = signal(String(prop('available').value ?? 'true') !== 'false');

    const decoder = document.createElement('textarea');

    const text = (value) => {
        decoder.innerHTML = String(value ?? '');

        return decoder.value;
    };

    const syncOffer = () => {
        if (!offerSelect) {
            return;
        }

        const option = offerSelect.options[offerSelect.selectedIndex];

        if (!option) {
            return;
        }

        selectedId.value = Number(option.value) || productId;
        available.value = option.dataset.available !== 'false';

        const root = $this.closest('[data-product-detail]');

        if (!root) {
            return;
        }

        const price = root.querySelector('[data-product-price]');

        if (price) {
            price.textContent = option.dataset.price
                ? text(option.dataset.price)
                : 'Цена по запросу';
        }

        const image = root.querySelector('[data-product-image]');

        if (image && option.dataset.image) {
            image.src = option.dataset.image;
        }
    };

    effect(() => {
        if (!offerSelect) {
            return;
        }

        const onChange = () => syncOffer();

        offerSelect.addEventListener('change', onChange);
        syncOffer();

        return () => offerSelect.removeEventListener('change', onChange);
    });

    const inCart = computed(() => selectedId.value > 0
        && basket.productIds.value.has(selectedId.value));
    const pending = computed(() => selectedId.value > 0
        && Boolean(basket.pending.value[`product:${selectedId.value}`]));

    const add = () => {
        if (!selectedId.value || inCart.value || pending.value || !available.value) {
            return;
        }

        basket.add(selectedId.value, 1);
    };

    return () => {
        if (inCart.value) {
            return html`
                <a href="/cart/" class="btn btn-success w-100">В корзине</a>
            `;
        }

        if (!available.value) {
            return html`<button type="button" class="btn btn-secondary w-100" disabled>Нет в наличии</button>`;
        }

        if (detailUrl) {
            return html`<a href=${detailUrl} class="btn btn-primary w-100">Купить</a>`;
        }

        if (pending.value) {
            return html`<button type="button" class="btn btn-primary w-100" disabled>Добавляем…</button>`;
        }

        return html`<button type="button" class="btn btn-primary w-100" onclick=${add}>Купить</button>`;
    };
});