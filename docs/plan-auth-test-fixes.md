# Тестирование и исправления модуля Auth

Статус: выполнено. e2e 18/18 (curl + БД, опции ядра проверялись переключением).

> Актуальность: документ исторический. После переписывания Auth (v3) часть исправлений
> была потеряна и восстановлена повторно (см. `bitrix-api-notes.md`). Решение об
> обязательной капче на login/confirm отменено: `CaptchaActionFilter` не навешен.
> Текущие маршруты — `/api/auth/confirm`, `/api/captcha`; resend удалён.

## План (выполнен)

1. Ревью `AuthService` / `AuthController` / `routes/api.php` / DTO / Swagger / капча-фильтра.
2. Исправление найденных багов (только `www/local/**`).
3. `php -l` батчем в контейнере `bx_shop_starter-php-1`.
4. e2e: captcha → login без капчи → register (с капчей/без, одноразовость sid) →
   me → logout (CSRF) → login (remember true/false, неверный пароль) →
   forgot → reset (неверный/верный checkword, неизвестный email) →
   confirm (без капчи, неверный код, верный код, повторный) → resend →
   register при включённом подтверждении (без авто-входа).
5. Cleanup тестовых пользователей (`CUser::Delete`), возврат опций ядра.

## Исправления

- `src/Auth/AuthService.php`:
  - `Bitrix\Security\Mfa\UserTable` (таблица OTP `b_sec_user`) заменена на
    `Bitrix\Main\UserTable` (`b_user`) в confirm/reset/resend.
  - В `select` добавлены `CONFIRM_CODE` (confirm) и `LOGIN` (reset) — раньше
    читались не выбранные поля (всегда null).
  - `register`: убраны чтение `USER_ID` до проверки ошибки, дубль письма
    `NEW_USER_CONFIRM` и двойной `Authorize` — `CUser::Register` сам шлёт письмо
    при опции подтверждения и сам авторизует при `ACTIVE='Y'`. Проверка результата
    до обращения к полям; обязательный параметр после необязательного убран.
  - `captchaSid` — string (sid капчи — 32-символьная строка, не int).
  - Авто-имя при регистрации: `'Пользователь ' . random_int(10000, 99999)` (по Swagger).
  - `confirmRegistration`: `(int) $user['ID']` для `Context::setUserId` (ORM отдаёт строку).
  - Добавлен `resendConfirmation` (одинаковый ответ для известного/неизвестного email).
  - Опечатка «подтерждения» → «подтверждения».
- `controllers/AuthController.php`:
  - `CaptchaActionFilter` применён к `login` и `confirmRegistration` (был объявлен,
    но не использован) — по итоговому решению `docs/plan-captcha-rate-limit.md`.
    register/resend без фильтра: капча регистрации — опция ядра `captcha_registration`.
  - `forgetPasswordAction` → `forgotPasswordAction` (совпадение с роутом).
  - Добавлен `resendConfirmationAction`.
  - `registerAction`: касты captcha-полей (string), поддержка JSON и form-data.
- `routes/api.php` (URL приведены к Swagger):
  - `/api/auth/confirm` → `/api/auth/register/confirm`;
  - добавлен `/api/auth/register/resend`;
  - `/api/captcha` → `/api/auth/captcha`.

## Результаты e2e

- Капча: обязательна с 1-го запроса login/confirm; без неё `captcha_required` +
  свежий sid/url; sid одноразовый; привязки к сессии нет (код в `b_captcha`).
- register: без капчи ошибка ядра (`captcha_registration=Y`); с капчей — успех,
  авто-имя, авто-вход (опция подтверждения=N); при подтверждении=Y — `ACTIVE=N`,
  без авто-входа, `CONFIRM_CODE` в `b_user`.
- confirm: без капчи `captcha_required`; неверный код — ошибка; верный — активация
  + авторизация; повторный — «Аккаунт уже подтверждён».
- resend: успех для неподтверждённого и неизвестного email (ответ одинаков).
- login: неверный пароль — ошибка; remember=false без cookie UIDH, true — с UIDH.
- logout: CSRF `sessid` только form-data/query (`check_bitrix_sessid` читает
  `$_REQUEST`, JSON-тело не работает — поведение ядра); после logout me → 401.
- forgot: успех, контрольное слово уходит письмом `NEW_USER_CONFIRM`
  (в БД хранится хеш `$6$...`, plaintext только в письме).
- reset: неверный checkword/неизвестный email — ошибки; верный — успех,
  вход новым паролем работает.
- me: 401 без авторизации, данные пользователя с авторизацией.

## Замечания (вне модуля auth, не правились)

- Стек-трейсы в ответах об ошибках — dev-режим `www/local/.settings_extra.php`
  (`exception_handling.debug=true`); в проде выключить.
- Почта в контейнере не доставляется (`/usr/sbin/sendmail` отсутствует) —
  инфраструктура, не код; события `NEW_USER`/`NEW_USER_CONFIRM` в `b_event_type` есть.
- Код расходится с `docs/bitrix-api-notes.md` (там описан вариант с `CUser::Add`
  и `UserService`); текущая реализация на `CUser::Register` рабочая, полный
  рефакторинг по заметкам — отдельная задача.
- Swagger logout не документирует передачу `sessid` (form-data/query) — стоит добавить.
