/**
 * Сортировка: разметку рисует PHP (shop:catalog.sort), здесь только данные.
 *
 * Выбор уходит в URL — catalog.js перезагружает список по изменению query.
 * При навигации назад/вперёд возвращаем значение селекта.
 *
 * Скрипт подключается в <head>, поэтому стартуем после разбора разметки.
 */
(() => {
    'use strict';

    const start = () => {
        const signa = window.signa;
        const select = document.querySelector('[data-sort]');

        if (!signa || !select) {
            return;
        }

        const param = select.dataset.param || select.name || 'sort';
        const defaultValue = select.dataset.default || '';

        const current = () => new URLSearchParams(window.location.search).get(param) || defaultValue;

        select.addEventListener('change', () => {
            signa.location.set({
                [param]: select.value === defaultValue ? null : select.value,
            });
        });

        window.addEventListener('popstate', () => {
            select.value = current();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
