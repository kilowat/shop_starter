defStore('url', ({ signal, computed }) => {
    const parse = () => {
        const params = new URLSearchParams(window.location.search);
        const result = {};

        for (const [rawKey, value] of params) {
            const isArray = rawKey.endsWith('[]');
            const key = isArray ? rawKey.slice(0, -2) : rawKey;

            if (isArray) {
                if (!result[key]) result[key] = [];
                result[key].push(value);
            } else {
                result[key] = value; // всегда последнее значение
            }
        }

        return result;
    };

    const stringify = (obj) => {
        const params = new URLSearchParams();

        Object.entries(obj).forEach(([key, value]) => {
            if (value == null) return;

            if (Array.isArray(value)) {
                value.forEach(v => params.append(`${key}[]`, v));
            } else {
                params.set(key, value);
            }
        });

        return params.toString();
    };

    const params = signal(parse());

    const search = computed(() => stringify(params.value));

    const updateUrl = (next, replace = true) => {
        const query = stringify(next);
        const url = `${location.pathname}${query ? '?' + query : ''}${location.hash}`;

        history.replaceState(null, '', url); // 🔥 всегда replace

        params.value = next;
    };

    // 🔹 универсальный set
    const set = (key, value) => {
        const next = { ...params.value };

        if (typeof key === 'object') {
            // массовое обновление
            Object.entries(key).forEach(([k, v]) => {
                if (v == null) {
                    delete next[k];
                } else {
                    next[k] = v;
                }
            });
        } else {
            if (value == null) {
                delete next[key];
            } else {
                next[key] = value;
            }
        }

        updateUrl(next);
    };

    const remove = (key) => {
        const next = { ...params.value };

        if (Array.isArray(key)) {
            key.forEach(k => delete next[k]);
        } else {
            delete next[key];
        }

        updateUrl(next);
    };

    const clear = () => {
        updateUrl({});
    };

    const get = (key) => params.value[key];

    const getAll = () => params.value;

    // 🔹 sync при back/forward
    const syncFromUrl = () => {
        params.value = parse();
    };

    window.addEventListener('popstate', syncFromUrl);

    // 🔹 перехват history
    const wrapHistory = (type) => {
        const original = history[type];

        history[type] = function (...args) {
            const result = original.apply(this, args);
            syncFromUrl();
            return result;
        };
    };

    wrapHistory('pushState');
    wrapHistory('replaceState');

    return {
        params,
        search,
        get,
        getAll,
        set,
        remove,
        clear,
    };
});