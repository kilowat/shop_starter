# Постраничная навигация

Пагинация — это штатный компонент `bitrix:system.pagenavigation`
(в Bitrix он называется именно так, не `system.pagination`).
В шаблоне сайта лежат три его шаблона:

| Шаблон | Файл | Поведение |
|---|---|---|
| `classic` | `classic/template.php` | Обычные ссылки. Клик — полная перезагрузка страницы. |
| `show_more` | `show_more/template.php` | Кнопка «Показать ещё». Следующая страница грузится AJAX-ом и дописывается в список. |
| `lazy` | `lazy/template.php` | Видимой пагинации нет. `IntersectionObserver` подгружает страницы при прокрутке. |

Общая логика — `www/local/templates/shop/js/pagination/pagination.js`.
Скрипт подключается глобально в `header.php`, поэтому подходит любому списку.

## Что нужно дописать в шаблоне своего списка

Пагинатор не знает, где находится список, — он ищет его по атрибутам.
В шаблоне списка (например, `catalog.section/shop_bootstrap/template.php`)
нужно добавить три атрибута:

```html
<div data-pagination-list>              <!-- 1 -->
    <div data-pagination-items>         <!-- 2 -->
        <article data-pagination-item>...</article> <!-- 3 -->
        <article data-pagination-item>...</article>
    </div>

    <!-- сюда выводится NAV_STRING / system.pagenavigation -->
</div>
```

Зачем каждый атрибут:

1. **`data-pagination-list`** — контейнер всего списка.
   JS ищет его в текущей странице и в HTML следующей страницы, чтобы понять,
   из какого блока брать элементы. Если у контейнера нет `id`, добавьте
   `data-pagination-key="уникальный-ключ"` — по нему JS найдёт этот же
   контейнер в ответе. В `catalog.section/shop_bootstrap` уже стоит
   `data-pagination-key="nav-{NavNum}"`.
2. **`data-pagination-items`** — контейнер, в который дописываются элементы.
   Для каталога это `.row` с карточками.
3. **`data-pagination-item`** — один элемент списка (карточка, строка, `<tr>`).
   Именно эти узлы JS переносит из следующей страницы в текущий список.

Больше ничего передавать через параметры компонента не нужно.
Если у списка особая разметка — просто добавьте эти атрибуты в его шаблон.

## Подключение

В `shop:catalog` есть только один параметр, выбирающий шаблон:

```php
'PAGINATION_TEMPLATE' => 'classic', // classic | show_more | lazy
```

Он прокидывается в `bitrix:catalog.section` как `PAGER_TEMPLATE`.
Для другого компонента достаточно передать `PAGER_TEMPLATE` со значением
`classic`, `show_more` или `lazy`.

## Тексты, спиннер и ленивая загрузка

Подписи и настройки не прокидываются через параметры — они лежат прямо
в шаблонах пагинатора:

- `show_more/template.php` — переменные `$labelMore`, `$labelLoading`,
  `$labelDone`, `$labelError`.
- `lazy/template.php` — переменные:
  - `$showSpinner` — показывать ли спиннер во время подгрузки (`true/false`);
  - `$rootMargin` — за сколько пикселей до конца списка начинать загрузку
    (по умолчанию `400px`);
  - `$labelLoading`, `$labelDone`, `$labelError`.

Спиннер находится под списком, в `[data-pagination-sentinel]`. Пока идёт
загрузка, на корне пагинатора появляется класс `is-loading`, и CSS показывает
`[data-pagination-spinner]`. Чтобы заменить спиннер, отредактируйте markup
в `lazy/template.php`; атрибут `data-pagination-spinner` нужен, если вы
хотите, чтобы показом/скрытием управлял CSS. Если спиннер не нужен —
поставьте `$showSpinner = false`.

## Поведение JS

- `classic` не перехватывает клики: работает обычная ссылка и перезагрузка.
- `show_more` перехватывает клик по `[data-pagination-more]`, блокирует
  повторную загрузку, дописывает элементы и обновляет `data-next-url`.
  Без JS кнопка остаётся обычной ссылкой.
- `lazy` следит за `[data-pagination-sentinel]`; когда sentinel попадает
  в зону `rootMargin`, грузится следующая страница. После последней страницы
  блок скрывается.

Следующая страница запрашивается обычным GET по `data-next-url`.
Из ответа берутся `[data-pagination-list]` → `[data-pagination-items]` →
`[data-pagination-item]`, поэтому серверу не нужен специальный AJAX-экшен.

После замены HTML списка (фильтр/сортировка в `catalog.js`) новые пагинаторы
инициализируются автоматически через `MutationObserver`.
