# Правила работы с проектом shop_starter (Bitrix)

## Что где лежит

- `www/local/**` — наш код: контроллеры (`controllers/`), сервисы (`src/`), роуты (`routes/`), шаблон (`templates/shop/`), `init.php`.
- `www/bitrix/**` — ядро и модули Bitrix. Только читаем, не правим.
- `www/bitrix/modules/<module>/lib/**` — D7-классы; `classes/general/**` и `include.php` — legacy.
- `www/local/vendor/**`, `www/upload/**` — зависимости и файлы; читать только при конкретной необходимости.
- `logs/` — php/nginx/db.

## Запрет на изменение ядра

- Всё, что лежит в `www/bitrix/**`, — только чтение: нельзя редактировать, переименовывать, удалять и создавать там файлы.
- Особенно `www/bitrix/modules/**`, `www/bitrix/components/**`, `www/bitrix/templates/**`, `www/bitrix/php_interface/**`.
- Не выполнять в ядре `sed -i`, `chmod`, `git checkout/clean`, миграции и SQL, меняющие его структуру.
- Обновления ядра — только штатным механизмом обновлений Bitrix; вручную файлы не трогаем.
- Если поведение ядра не устраивает — переопределяем через `www/local/**`: свои компоненты и шаблоны, обработчики событий в `local/init.php` и `local/src/event.php`.
- Если без правки ядра не обойтись — остановиться, согласовать с человеком и зафиксировать причину; патч ядра — крайний случай, а не рабочая практика.

## Приоритет источников

1. Код в `www/local/**` — как проект уже решает задачу.
2. Схема БД и логи (`docker exec bx_shop_starter-db-1 mysql ...`, `logs/`).
3. Официальная документация Bitrix (dev.1c-bitrix.ru, api_d7) — с проверкой версии модуля.
4. Ядро `www/bitrix/**` — когда без него не понять поведение.

## Как читать

- Сначала `grep`/`glob`, затем `read`. Ищи по модулю или конкретному каталогу, а не по всему `www/bitrix`.
- Читай столько, сколько нужно для ответа: сигнатуру, вызовы, ключевые ветки. Если для понимания нужен файл целиком — читай целиком, но не дампи его в ответ.
- Большие файлы удобнее читать через `offset`/`limit`; можно возвращаться и дочитывать.
- Не читай `install/`, `lang/`, `themes/`, `vendor/`, `upload/` без конкретной необходимости.
- Ядро не редактируем: любые изменения — только в `www/local/**`.

## Навигация по Bitrix

- D7: `Bitrix\Sale\Basket` → `www/bitrix/modules/sale/lib/basket.php` (namespace = путь).
- Legacy: ищи `class CSaleBasket` в `www/bitrix/modules/sale` (обычно `classes/general/` или `include.php`).
- Компонент: `www/bitrix/components/bitrix/<name>/class.php` и `templates/`; кастомные — `www/local/components/`.
- Шаблон сайта: `www/local/templates/shop/` (`header.php`, `footer.php`, `components/bitrix/...`).
- События/хуки: `www/local/init.php`, `www/local/src/event.php`.
- Роуты/контроллеры: `www/local/routes/*.php`, `www/local/controllers/`.
- Автозагрузка: `www/local/composer.json` (`App\` → `src/`, `Controllers\` → `controllers/`).
- Версия модуля: `www/bitrix/modules/<module>/install/version.php`.

## Проверка

- Синтаксис: `php -l` в контейнере `bx_shop_starter-php-1`.
- Поведение: запускай код в контейнере (`docker exec ... php`), смотри `logs/php`.
- Перед выводами об API сверяй версию установленного модуля с документацией.

## Экономия контекста

- Не перечитывай уже разобранное. Если возвращаешься к одному и тому же API — короткая заметка в `docs/bitrix-api-notes.md` (по желанию, файл не обязателен).
- Если задача требует много чтения — сначала план и оценка объёма, потом работа.
- В ответе — выводы и ссылки `path:line`, а не большие куски кода.
