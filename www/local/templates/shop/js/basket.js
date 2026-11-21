signa.state('basket', ({ signal, computed }, { useFetch, notice }) => {
    const normalizePreview = (state) => ({
        count: Math.max(0, Number(state?.count) || 0),
        productIds: new Set(
            Array.isArray(state?.productIds)
                ? state.productIds.map(Number).filter((id) => id > 0)
                : []
        ),
    });

    const initialPreview = window.__shopBasketSeed;
    const preview = signal(initialPreview ? normalizePreview(initialPreview) : null);
    const previewLoading = signal(false);

    const data = signal(null);
    const loading = signal(false);
    const pending = signal({});
    const error = signal('');

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const headers = csrf ? { 'X-Bitrix-Csrf-Token': csrf } : {};

    const api = {
        state: useFetch('/api/basket/state', { cache: 'no-store', headers }),
        show: useFetch('/api/basket', { cache: 'no-store', headers }),
        add: useFetch('/api/basket/items', { method: 'POST', headers }),
        quantity: useFetch('/api/basket/items/quantity', { method: 'POST', headers }),
        delay: useFetch('/api/basket/items/delay', { method: 'POST', headers }),
        remove: useFetch('/api/basket/items/remove', { method: 'POST', headers }),
        clear: useFetch('/api/basket/clear', { method: 'POST', headers }),
        coupon: useFetch('/api/basket/coupon', { method: 'POST', headers }),
        couponRemove: useFetch('/api/basket/coupon/remove', { method: 'POST', headers }),
    };

    let previewRequest = null;

    const loadState = () => {
        if (previewLoading.value) {
            return previewRequest || Promise.resolve(false);
        }

        previewLoading.value = true;

        previewRequest = (async () => {
            try {
                const result = await api.state.execute();

                if (!result.ok) {
                    return false;
                }

                preview.value = normalizePreview(result.data);

                return true;
            } catch (e) {
                return false;
            } finally {
                previewLoading.value = false;
                previewRequest = null;
            }
        })();

        return previewRequest;
    };

    const seed = (state) => {
        if (!state) {
            return;
        }

        window.__shopBasketSeed = state;
        preview.value = normalizePreview(state);
    };

    if (preview.value === null) {
        loadState();
    }

    let queue = Promise.resolve();

    const enqueue = (task) => {
        const run = queue.then(task, task);
        queue = run.catch(() => {});

        return run;
    };

    const items = computed(() => data.value?.items ?? []);
    const coupons = computed(() => data.value?.coupons ?? []);
    const summary = computed(() => data.value?.summary ?? null);
    const ready = computed(() => data.value !== null || preview.value !== null);
    const quantity = computed(() => {
        if (data.value !== null) {
            return items.value.reduce(
                (total, item) => total + Number(item.quantity || 0),
                0
            );
        }

        return preview.value?.count ?? 0;
    });
    const productIds = computed(() => {
        if (data.value === null) {
            return preview.value?.productIds ?? new Set();
        }

        const ids = new Set();

        items.value.forEach((item) => {
            if (item.product_id) {
                ids.add(Number(item.product_id));
            }

            if (item.parent_id) {
                ids.add(Number(item.parent_id));
            }
        });

        return ids;
    });

    const mark = (key, delta) => {
        const next = { ...pending.value };
        const value = (next[key] || 0) + delta;

        if (value > 0) {
            next[key] = value;
        } else {
            delete next[key];
        }

        pending.value = next;
    };

    const apply = (result) => {
        if (!result.ok) {
            error.value = result.errors?.[0]?.message || 'Не удалось обновить корзину';
            notice.error(error.value);

            return false;
        }

        error.value = '';
        data.value = result.data;

        return true;
    };

    const run = (name, payload, key = '') => {
        if (key) {
            mark(key, 1);
        }

        return enqueue(async () => {
            try {
                return apply(await api[name].execute(payload));
            } catch (e) {
                error.value = 'Не удалось обновить корзину';
                notice.error(error.value);

                return false;
            } finally {
                if (key) {
                    mark(key, -1);
                }
            }
        });
    };

    const refresh = () => {
        loading.value = true;

        return enqueue(async () => {
            try {
                return apply(await api.show.execute());
            } catch (e) {
                error.value = 'Не удалось загрузить корзину';
                notice.error(error.value);

                return false;
            } finally {
                loading.value = false;
            }
        });
    };

    const add = (productId, value = 1) => run(
        'add',
        { productId, quantity: value },
        `product:${productId}`
    );

    const updateQuantity = (basketId, value) => run(
        'quantity',
        { basketId, quantity: value },
        `item:${basketId}`
    );

    const setDelay = (basketId, delay = true) => run(
        'delay',
        { basketId, delay: delay ? '1' : '0' },
        `item:${basketId}`
    );

    const remove = (basketId) => run('remove', { basketId }, `item:${basketId}`);

    const clear = () => run('clear', {}, 'clear');

    const applyCoupon = async (coupon) => {
        const ok = await run('coupon', { coupon }, 'coupon');

        if (!ok) {
            return { success: false, message: error.value || 'Не удалось применить купон' };
        }

        const result = data.value?.coupon_result;

        if (result?.success) {
            notice.success(result.message || 'Купон применён');

            return { success: true, message: '' };
        }

        return { success: false, message: result?.message || 'Не удалось применить купон' };
    };

    const removeCoupon = (coupon) => run('couponRemove', { coupon }, 'coupon');

    const isInCart = (productId) => productIds.value.has(Number(productId));

    let lastSync = 0;

    const sync = () => {
        const now = Date.now();

        if (now - lastSync < 10000) {
            return;
        }

        lastSync = now;

        if (data.value !== null) {
            refresh();
        } else {
            loadState();
        }
    };

    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            sync();
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            sync();
        }
    });

    return {
        data,
        loading,
        pending,
        error,
        items,
        coupons,
        summary,
        ready,
        quantity,
        productIds,
        seed,
        refresh,
        add,
        updateQuantity,
        setDelay,
        remove,
        clear,
        applyCoupon,
        removeCoupon,
        isInCart,
    };
});