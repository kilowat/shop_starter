signa.component('cart-page', ({ html, signal }, { basket }) => {
    if (basket.data.value === null && !basket.loading.value) {
        basket.refresh();
    }

    const couponError = signal('');

    const decoder = document.createElement('textarea');

    const text = (value) => {
        decoder.innerHTML = String(value ?? '');

        return decoder.value;
    };

    const isPending = (id) => Boolean(basket.pending.value[`item:${id}`]);

    const step = (item, direction) => {
        const ratio = Number(item.measure_ratio) > 0 ? Number(item.measure_ratio) : 1;
        const current = Number(item.quantity) || 0;
        const max = item.available_quantity === null || item.available_quantity === undefined
            ? null
            : Number(item.available_quantity);
        let next = current + direction * ratio;

        if (max !== null && next > max) {
            next = max;
        }

        if (next <= 0) {
            basket.remove(item.id);

            return;
        }

        basket.updateQuantity(item.id, next);
    };

    const changeQuantity = async (item, event) => {
        const input = event.currentTarget;
        const value = Number(input.value);

        if (!Number.isFinite(value) || value <= 0) {
            input.value = String(item.quantity);

            return;
        }

        const ok = await basket.updateQuantity(item.id, value);

        if (!ok) {
            input.value = String(item.quantity);
        }
    };

    const submitCoupon = async (event) => {
        event.preventDefault();

        const form = event.currentTarget;
        const field = form.elements.coupon;
        const value = String(field?.value || '').trim();

        if (!value) {
            return;
        }

        couponError.value = '';
        const result = await basket.applyCoupon(value);

        if (result.success) {
            form.reset();

            return;
        }

        couponError.value = result.message || 'Не удалось применить купон';
    };

    const clear = () => {
        if (window.confirm('Очистить корзину?')) {
            basket.clear();
        }
    };

    const renderLoading = () => html`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
            <p class="text-muted mt-3 mb-0">Загружаем корзину…</p>
        </div>
    `;

    const renderError = () => html`
        <div class="alert alert-danger d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3" role="alert">
            <span>${basket.error.value || 'Не удалось загрузить корзину'}</span>
            <button class="btn btn-sm btn-outline-danger" type="button" onclick=${() => basket.refresh()}>Повторить</button>
        </div>
    `;

    const renderHeader = () => html`
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h1 class="h3 mb-0">Корзина</h1>

            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-outline-secondary btn-sm" href="/catalog/">Продолжить покупки</a>
                <button class="btn btn-outline-danger btn-sm" type="button" onclick=${clear} ?disabled=${Boolean(basket.pending.value.clear)}>Очистить</button>
            </div>
        </div>
    `;

    const renderEmpty = () => html`
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <h2 class="h4 mb-2">Корзина пуста</h2>
                <p class="text-muted mb-4">Добавьте товары из каталога, чтобы оформить заказ.</p>
                <a class="btn btn-primary" href="/catalog/">Перейти в каталог</a>
            </div>
        </div>
    `;

    const renderItem = (item) => {
        const pending = isPending(item.id);
        const unavailable = item.can_buy === false;
        const max = item.available_quantity;
        const canIncrease = !pending
            && !unavailable
            && (max === null || max === undefined || Number(item.quantity) < Number(max));
        const canDecrease = !pending && !unavailable;
        const image = item.image?.resized?.src || '';
        const url = item.offer_url || item.detail_url || '';
        const ratio = Number(item.measure_ratio) > 0 ? Number(item.measure_ratio) : 1;
        const hasDiscount = Number(item.prices?.discount?.percent) > 0;
        const picture = image
            ? html`<img src=${image} alt=${item.name} width="88" height="88" class="rounded object-fit-cover bg-light flex-shrink-0" loading="lazy">`
            : html`<div class="rounded bg-light flex-shrink-0" style="width: 88px; height: 88px;"></div>`;

        return html`
            <div class="list-group-item py-3">
                <div class="d-flex flex-column flex-sm-row gap-3">
                    ${url ? html`<a href=${url} class="d-block flex-shrink-0">${picture}</a>` : picture}

                    <div class="flex-grow-1">
                        <a href=${url || '#'} class="fw-semibold text-body text-decoration-none">${item.name}</a>${item.variant ? html` <span class="text-muted">(${item.variant})</span>` : null}

                        ${item.props?.length
                            ? html`<div class="small text-muted mt-1">${item.props.map((prop) => html`<div>${prop.name}: ${prop.value}</div>`)}</div>`
                            : null}

                        ${item.delay
                            ? html`<div class="mt-2"><span class="badge text-bg-secondary">Отложен</span> <button class="btn btn-sm btn-link px-1" type="button" onclick=${() => basket.setDelay(item.id, false)} ?disabled=${pending}>Вернуть в корзину</button></div>`
                            : null}

                        ${unavailable
                            ? html`<div class="mt-2"><span class="badge text-bg-warning">Недоступен</span></div>`
                            : null}

                        <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                            <div class="input-group input-group-sm" style="max-width: 150px;">
                                <button class="btn btn-outline-secondary" type="button" onclick=${() => step(item, -1)} ?disabled=${!canDecrease}>−</button>
                                <input class="form-control text-center" type="number" min="0" step=${ratio} .value=${String(item.quantity)} onchange=${(event) => changeQuantity(item, event)} ?disabled=${pending}>
                                <button class="btn btn-outline-secondary" type="button" onclick=${() => step(item, 1)} ?disabled=${!canIncrease}>+</button>
                            </div>

                            <span class="small text-muted">${text(item.prices.price.final_formatted)} за шт.</span>
                        </div>
                    </div>

                    <div class="text-sm-end">
                        <div class="fw-semibold">${text(item.prices.sum.final_formatted)}</div>

                        ${hasDiscount
                            ? html`<div class="small text-muted"><s>${text(item.prices.sum.base_formatted)}</s></div>`
                            : null}

                        <button class="btn btn-sm btn-link text-danger px-0" type="button" onclick=${() => basket.remove(item.id)} ?disabled=${pending}>Удалить</button>
                    </div>
                </div>
            </div>
        `;
    };

    const renderCoupons = (coupons) => html`
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3">Купон</h2>

                <form onsubmit=${submitCoupon}>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <div class="flex-grow-1">
                            <input class=${couponError.value ? 'form-control is-invalid' : 'form-control'} type="text" name="coupon" placeholder="Код купона" autocomplete="off" aria-invalid=${couponError.value ? 'true' : 'false'} oninput=${() => { couponError.value = ''; }} ?disabled=${Boolean(basket.pending.value.coupon)}>
                            ${couponError.value
                                ? html`<div class="invalid-feedback d-block">${couponError.value}</div>`
                                : null}
                        </div>
                        <button class="btn btn-outline-primary" type="submit" ?disabled=${Boolean(basket.pending.value.coupon)}>Применить</button>
                    </div>
                </form>

                ${coupons.length
                    ? html`<ul class="list-unstyled mt-3 mb-0">
                        ${coupons.map((coupon) => html`
                            <li class="d-flex justify-content-between align-items-center gap-2 py-1">
                                <span><span class="fw-semibold">${coupon.coupon}</span> <span class="small text-muted">${coupon.status_text}</span></span>
                                <button class="btn btn-sm btn-link text-danger px-0" type="button" onclick=${() => basket.removeCoupon(coupon.coupon)} ?disabled=${Boolean(basket.pending.value.coupon)}>Удалить</button>
                            </li>
                        `)}
                    </ul>`
                    : null}
            </div>
        </div>
    `;

    const renderSummary = (summary) => html`
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Товары (${summary.count})</span>
                    <span>${text(summary.base_formatted)}</span>
                </div>

                ${Number(summary.discount?.value) > 0
                    ? html`<div class="d-flex justify-content-between mb-2 text-danger">
                        <span>Скидка</span>
                        <span>−${text(summary.discount.value_formatted)}</span>
                    </div>`
                    : null}

                <hr>

                <div class="d-flex justify-content-between fs-5 fw-semibold">
                    <span>Итого</span>
                    <span>${text(summary.final_formatted)}</span>
                </div>
            </div>
        </div>
    `;

    return () => {
        if (basket.loading.value && basket.data.value === null) {
            return renderLoading();
        }

        if (!basket.data.value) {
            return basket.error.value ? renderError() : renderLoading();
        }

        const items = basket.items.value;
        const coupons = basket.coupons.value;
        const summary = basket.summary.value;

        if (!items.length) {
            return html`${renderHeader()}${renderEmpty()}`;
        }

        return html`
            ${renderHeader()}

            <div class="row g-3">
                <div class="col-12 col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="list-group list-group-flush">
                            ${items.map((item) => renderItem(item))}
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-4">
                    <div class="position-sticky" style="top: 1rem;">
                        ${renderCoupons(coupons)}
                        ${summary ? renderSummary(summary) : null}
                    </div>
                </div>

                <div class="col-12 col-lg-8 order-last">
                    <a class="btn btn-primary btn-lg w-100" href="/order/index.php">Оформить</a>
                </div>
            </div>
        `;
    };
});