/**
 * Запросы к API — Nuxt-style useFetch поверх signa.state.
 *
 * const { data, errors, ok, pending, execute, refresh } = useFetch(url, {
 *     method: 'POST',        // по умолчанию GET
 *     body: { ... },         // данные по умолчанию (можно передать в execute)
 *     query: { ... },        // query-параметры (объект или URLSearchParams)
 *     immediate: true,       // выполнить сразу при создании (по умолчанию нет)
 *     ...fetchOptions        // остальное уходит в fetch: headers, cache и т.д.
 * });
 *
 * await execute({ email, password }); // обычный объект сам станет FormData
 * await refresh();                    // повторить последний запрос
 *
 * Состояние — сигналы signa: data, errors, ok, pending.
 * Контракт ответа: { status: 'success'|'error', data, errors: [{ code, message }] }.
 * Сетевые ошибки не бросаются: errors = [{ code: 'NETWORK_ERROR', message }].
 */

signa.state('useFetch', ({ signal }) => {
    const toFormData = (data) => {
        const form = new FormData();

        for (const [key, value] of Object.entries(data)) {
            if (value !== undefined && value !== null) {
                form.append(key, value);
            }
        }

        return form;
    };

    const encodeBody = (data, method) => {
        if (data == null || method === 'GET' || method === 'HEAD') {
            return null;
        }

        if (data instanceof FormData || data instanceof URLSearchParams || typeof data === 'string') {
            return data;
        }

        return toFormData(data);
    };

    const withQuery = (url, query) => {
        if (!query) {
            return url;
        }

        const params = query instanceof URLSearchParams ? query : new URLSearchParams(query);

        return `${url}${url.includes('?') ? '&' : '?'}${params}`;
    };

    return (url, options = {}) => {
        const { method = 'GET', body = null, query = null, immediate = false, ...fetchOptions } = options;

        const data = signal(null);
        const errors = signal([]);
        const ok = signal(false);
        const pending = signal(false);

        let lastBody = body;

        const execute = async (payload = lastBody) => {
            lastBody = payload;

            pending.value = true;
            errors.value = [];

            try {
                const response = await fetch(withQuery(url, query), {
                    method,
                    ...fetchOptions,
                    body: encodeBody(payload, method),
                });
                const json = (await response.json().catch(() => null)) || {};
                const success = Boolean(response.ok && json.status === 'success');

                // data не сбрасываем при ошибке: последние успешные данные остаются доступны.
                if (success) {
                    data.value = json.data !== undefined ? json.data : null;
                }

                errors.value = Array.isArray(json.errors) ? json.errors : [];
                ok.value = success;
            } catch (e) {
                errors.value = [{ code: 'NETWORK_ERROR', message: 'Не удалось связаться с сервером' }];
                ok.value = false;
            } finally {
                pending.value = false;
            }

            return { ok: ok.value, data: data.value, errors: errors.value };
        };

        const refresh = () => execute(lastBody);

        if (immediate) {
            execute();
        }

        return { data, errors, ok, pending, execute, refresh };
    };
});
