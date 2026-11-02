BX.ready(function () {

    // ─── Помощники ──────────────────────────────────────────────────────────

    function getUrlParam(key) {
        return new URLSearchParams(window.location.search).get(key);
    }

    function buildUrl(enable) {
        var url = new URL(window.location.href);
        if (enable) {
            url.searchParams.set('lang', 'DEBUG');
        } else {
            url.searchParams.delete('lang');
        }
        return url.toString();
    }

    // ─── Панель (ВАШИ ИСХОДНЫЕ СТИЛИ) ───────────────────────────────────────

    function injectPanelStyles() {
        var style = document.createElement('style');
        style.textContent = [
            '#loc-debug-btn-group {',
            '    display: inline-flex;',
            '    align-items: center;',
            '}',
            '#loc-debug-toggle {',
            '    display: inline-flex !important;',
            '    align-items: center;',
            '    gap: 4px;',
            '    text-decoration: none;',
            '}',
            '#loc-debug-icon {',
            '    width: 16px;',
            '    height: 16px;',
            '    display: flex !important;',
            '    align-items: center;',
            '    justify-content: center;',
            '    color: #fff;',
            '    background: none !important;',
            '    flex-shrink: 0;',
            '}',
            '#loc-debug-icon svg {',
            '    display: block;',
            '    transition: transform .2s;',
            '     position: relative;',
            '     top: 6px;',
            '}',
            '#loc-debug-toggle:hover #loc-debug-icon svg {',
            '    transform: rotate(20deg);',
            '}',
            '.loc-debug-active #loc-debug-toggle {',
            '    color: #ff9800 !important;',
            '}',
            '.loc-debug-active #loc-debug-icon {',
            '    color: #ff9800 !important;',
            '}',
            '.loc-debug-active .bx-panel-small-button-text {',
            '    color: #ff9800 !important;',
            '}',
            '#loc-debug-dot {',
            '    display: inline-block;',
            '    width: 5px;',
            '    height: 5px;',
            '    border-radius: 50%;',
            '    background: #888;',
            '    margin-left: 3px;',
            '    vertical-align: middle;',
            '    transition: background .15s, box-shadow .15s;',
            '}',
            '.loc-debug-active #loc-debug-dot {',
            '    background: #ff9800;',
            '    box-shadow: 0 0 5px #ff9800;',
            '}',
            ' t-word[id] {',
            '   scroll-margin-top: 100px;',
            ' }'
        ].join('\n');
        document.head.appendChild(style);
    }

    function injectPanelButton() {
        var buttonsInner = document.getElementById('bx-panel-buttons-inner');
        if (!buttonsInner) return;

        var active = getUrlParam('lang') === 'DEBUG';
        var ICON_SVG = [
            '<svg xmlns="http://w3.org" width="16" height="16" viewBox="0 0 16 16" fill="none">',
            '  <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.2"/>',
            '  <ellipse cx="8" cy="8" rx="2.8" ry="6.5" stroke="currentColor" stroke-width="1.2"/>',
            '  <line x1="1.5" y1="5.5" x2="14.5" y2="5.5" stroke="currentColor" stroke-width="1.2"/>',
            '  <line x1="1.5" y1="10.5" x2="14.5" y2="10.5" stroke="currentColor" stroke-width="1.2"/>',
            '</svg>',
        ].join('');

        var separator = document.createElement('span');
        separator.className = 'bx-panel-button-separator';

        var group = document.createElement('span');
        group.className = 'bx-panel-button-group' + (active ? ' loc-debug-active' : '');
        group.id = 'loc-debug-btn-group';

        var btn = document.createElement('span');
        btn.className = 'bx-panel-small-button' + (active ? ' bx-panel-small-button-text-active' : '');

        var inner = document.createElement('span');
        inner.className = 'bx-panel-small-button-inner';

        var link = document.createElement('a');
        link.href = buildUrl(!active);
        link.id = 'loc-debug-toggle';
        link.title = active ? 'Выключить режим перевода' : 'Включить режим перевода';

        link.innerHTML = [
            '<span id="loc-debug-icon" class="bx-panel-small-button-icon">' + ICON_SVG + '</span>',
            '<span class="bx-panel-small-button-text">',
            '    Перевод<span id="loc-debug-dot"></span>',
            '</span>',
        ].join('');

        inner.appendChild(link);
        btn.appendChild(inner);
        group.appendChild(btn);

        buttonsInner.appendChild(separator);
        buttonsInner.appendChild(group);
    }

    // ─── Универсальное окно (Pop-up) с адаптивными размерами ───────────────

    function openHLDialog(url, title, ufCode = null) {
        var screenWidth = window.innerWidth;
        var screenHeight = window.innerHeight;
        var dialogWidth = Math.min(1100, screenWidth - 100);
        var dialogHeight = Math.min(800, screenHeight - 150);

        var dialog = new BX.CDialog({
            title: title,
            content_url: url,
            width: dialogWidth,
            height: dialogHeight,
            resizable: true,
            draggable: true,
            buttons: [
            ]
        });

        dialog.Show();

        // Ждем загрузки контента для вставки UF_CODE
        BX.addCustomEvent(dialog, 'onWindowRegister', function () {
            var dialogContent = dialog.GetContent();
            if (ufCode) {
                var input = dialogContent.querySelector('input[name="UF_CODE"]');
                if (input) {
                    input.value = ufCode;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });
    }

    // ─── Инициализация кликов и слов ─────────────────────────────────────────

    function initWords() {
        document.querySelectorAll('t-word[data-hid]').forEach(function (n) {
            n.classList.add('loc-edit-word');
            var isNew = !n.dataset.itemid || n.dataset.itemid === '';
            n.setAttribute('title', isNew ? 'Создать перевод для: ' + (n.dataset.uf_code || '') : 'Редактировать перевод');
        });
    }

    function initClickHandler() {
        document.addEventListener('click', function (e) {
            var node = e.target.closest('t-word[data-hid]');
            if (!node) return;

            e.preventDefault();
            e.stopPropagation();

            // ✅ ДОБАВЛЯЕМ ЯКОРЬ В URL
            // Если у t-word есть id, используем его, иначе можно использовать uf_code
            if (node.id) {
                window.location.hash = node.id;
            }

            var hid = node.dataset.hid;
            var itemId = node.dataset.itemid;
            var ufCode = node.dataset.uf_code;

            var baseUrl = '/bitrix/admin/highloadblock_row_edit.php?ENTITY_ID=' + encodeURIComponent(hid) + '&lang=ru&bxpublic=Y';

            if (itemId && itemId !== '') {
                openHLDialog(baseUrl + '&ID=' + encodeURIComponent(itemId), 'Редактировать перевод');
            } else {
                openHLDialog(baseUrl, 'Создать перевод: ' + ufCode, ufCode);
            }
        });
    }

    injectPanelStyles();
    injectPanelButton();
    initWords();
    initClickHandler();

});
