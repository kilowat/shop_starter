(function (global) {

    function I18n(options) {
        this.endpoint = options.endpoint || '/l.php';
        this.cache = {};
        this.queue = new Set();
        this.loading = null;
        this.pendingNodes = {}; // code -> [nodes]
        this.isDebug = new URLSearchParams(location.search).get('lang') === 'DEBUG';
    }

    I18n.prototype._normalizeLang = function (lang) {
        return (lang || '').toUpperCase();
    };

    // Асинхронно получить одно значение
    I18n.prototype.get = function (code) {
        var self = this;

        if (this.cache[code] !== undefined) {
            return Promise.resolve(this.cache[code]);
        }

        this.queue.add(code);

        return this.flush().then(function () {
            return self.cache[code] !== undefined ? self.cache[code] : code;
        });
    };

    // Предзагрузить несколько кодов
    I18n.prototype.load = function (codes) {
        var self = this;
        codes.forEach(function (c) {
            if (self.cache[c] === undefined) self.queue.add(c);
        });
        return this.flush();
    };

    // Отправить накопившуюся очередь батчем
    I18n.prototype.flush = function () {
        var self = this;

        if (this.loading) return this.loading;

        var codes = Array.from(this.queue);
        this.queue.clear();

        if (!codes.length) return Promise.resolve();

        this.loading = fetch(this.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                codes: codes
            })
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (res) {
                var data = res.data || {};
                Object.keys(data).forEach(function (k) {
                    self.cache[k] = data[k];
                    // Обновляем DOM-узлы, которые ждали этот код
                    self._flushPendingNodes(k);
                });
            })
            .catch(function (err) {
                console.warn('[i18n] flush error:', err);
            })
            .finally(function () {
                self.loading = null;
            });

        return this.loading;
    };

    // Обновить DOM-узлы, которые ждали конкретный код
    I18n.prototype._flushPendingNodes = function (code) {
        var nodes = this.pendingNodes[code];
        if (!nodes) return;
        var text = this.cache[code] || code;
        nodes.forEach(function (node) {
            if (node && node.parentNode) {
                if (node.nodeType === Node.TEXT_NODE) {
                    node.nodeValue = text;
                } else {
                    node.textContent = text;
                }
            }
        });
        delete this.pendingNodes[code];
    };

    // translate по карте языков (как PHP)
    I18n.prototype.translate = function (map, lang) {
        lang = this._normalizeLang(lang);
        if (lang && map[lang] !== undefined) return this._value(map[lang]);
        var keys = Object.keys(map);
        if (keys.length) return this._value(map[keys[0]]);
        return '';
    };

    I18n.prototype._value = function (v) {
        return (typeof v === 'function') ? v() : v;
    };

    // Сканировать DOM и переводить элементы с data-i18n / data-uf_code / code
    I18n.prototype.scan = function () {
        var self = this;
        var nodes = document.querySelectorAll('[data-i18n], t-word[data-uf_code], i18n-text');
        var codes = [];

        nodes.forEach(function (el) {
            var code =
                el.getAttribute('data-i18n') ||
                el.getAttribute('data-uf_code') ||
                el.getAttribute('code');
            if (code) codes.push(code);
        });

        return this.load(codes).then(function () {
            nodes.forEach(function (el) {
                self.applyToNode(el);
            });
        });
    };

    I18n.prototype.applyToNode = function (el) {
        var code =
            el.getAttribute('data-i18n') ||
            el.getAttribute('data-uf_code') ||
            el.getAttribute('code');
        if (!code) return;
        if (el.tagName === 'T-WORD' && this.isDebug) return; // debug — не трогаем
        var text = this.cache[code] !== undefined ? this.cache[code] : code;
        el.textContent = text;
    };

    // --- WebComponent <i18n-text code="SALE"> ---
    function defineI18nText(t) {
        if (customElements.get('i18n-text')) return;

        var I18nTextProto = Object.create(HTMLElement.prototype);
        I18nTextProto.connectedCallback = function () {
            var code = this.getAttribute('code');
            if (!code) return;
            var el = this;
            if (t.cache[code] !== undefined) {
                el.textContent = t.cache[code];
            } else {
                t.get(code).then(function (text) {
                    el.textContent = text;
                });
            }
        };

        customElements.define('i18n-text', I18nTextProto);
    }

    // --- Глобальный инстанс ---
    var t = new I18n({
        endpoint: '/l.php'
    });

    // --- Главная функция __t() ---
    // Если код уже в кеше — возвращает значение сразу.
    // Если нет — возвращает placeholder и обновляет DOM автоматически
    // когда ответ придёт, ИЛИ можно передать callback/элемент.
    //
    // Варианты вызова:
    //   __t('SALE')              → строка из кеша или код (фоновая загрузка)
    //   __t('SALE', el)          → запишет в el.textContent когда загрузится
    //   __t({RU:'Аренда',...})   → translate по карте языков
    //   __t('SALE').then(...)    → Promise-режим через t.get()
    function __t(codeOrMap, langOrEl) {

        // Карта языков — синхронно
        if (typeof codeOrMap === 'object' && codeOrMap !== null) {
            return t.translate(codeOrMap, typeof langOrEl === 'string' ? langOrEl : null);
        }

        var code = codeOrMap;

        // Уже в кеше — вернуть сразу
        if (t.cache[code] !== undefined) {
            return t.cache[code];
        }

        // Передан DOM-элемент — обновим его когда придёт ответ
        if (langOrEl && langOrEl.nodeType) {
            if (!t.pendingNodes[code]) t.pendingNodes[code] = [];
            t.pendingNodes[code].push(langOrEl);
        }

        // Ставим в очередь и грузим
        t.queue.add(code);
        t.flush();

        // Возвращаем пустую строку (не код) пока грузится,
        // чтобы не показывать сырой код пользователю
        return '';
    }

    // Promise-вариант для случаев когда нужно await
    __t.async = function (code) {
        return t.get(code);
    };

    // Авто-запуск после загрузки DOM
    document.addEventListener('DOMContentLoaded', function () {
        t.scan();
        defineI18nText(t);
    });

    global.t = t;
    global.__t = __t;

})(window);