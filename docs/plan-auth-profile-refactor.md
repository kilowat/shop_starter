# Рефакторинг Auth / Profile / User

Статус: реализовано. e2e 20/20 (12 auth + 8 profile). Развилки подтверждены.

> Актуальность: документ исторический. После переписывания Auth (v3) `UserService`
> удалён; `ProfileService::create`, `requestPasswordChange` и `resend` убраны;
> маршруты — `/api/auth/confirm`, `/api/captcha`; `updateProfile` принимает
> `ProfilePayload` (NAME/PERSONAL_PHONE). Актуальное состояние — `project-map.md`
> и `bitrix-api-notes.md`.

## Разделение ответственности

- **Auth** (`AuthService` + `AuthController`): залогинить, разлогинить, зарегистрировать
  (+confirm/resend), показать текущего (me), выдать код восстановления (forgotPassword)
  и завершить восстановление (resetPassword).
- **Profile** (`ProfileService` + новый `ProfileController`): создать пользователя для
  заказа (`create`, бывший `registerByEmail`), изменить данные (`updateProfile`),
  инициировать смену пароля (`requestPasswordChange`), удалить профиль (`deleteProfile`, новый).
- **User** (`UserService`): поиск пользователя + манипуляции с ним в БД (create/update/delete)
  и общие хелперы идентичности/создания. Единое место работы с `b_user`.
- **Captcha** (`CaptchaService` + новый `CaptchaController`): генерация/проверка капчи.
  `getCaptcha` из `AuthService` убираем; капча отдаётся отдельным контроллером.

Подтверждённые развилки: отдельный ProfileController + неймспейс `/api/profile/*`;
`resetPassword` в Auth, `requestPasswordChange` в Profile; `deleteProfile` добавляем.

## UserService (`src/Auth/UserService.php`)

Поиск (оставить, упростить — убрать алиас `findActiveByEmail`):
- `find(email, onlyActive=true): ?array`, `findOrFail(email, msg, onlyActive=true): array`,
  `emailExists(email): bool`, `getData(id): array`, private `findByEmail`.

БД-манипуляции (перенести из сервисов, обёртки над CUser с броском SystemException):
- `create(array $fields): int` — `CUser::Add` (из `ProfileService::registerByEmail`).
- `update(int $id, array $fields): void` — `CUser::Update` (из updateProfile/savePhone/confirm).
- `delete(int $id): void` — мягкое удаление: деактивация `ACTIVE='N'` через `CUser::Update`
  (физически запись не удаляется) — для deleteProfile.

Общие хелперы (чтобы не дублировать между register и create):
- `normalizeEmail`, `assertEmailAvailable`, `generateDisplayName`, `defaultGroups`, `siteId`.

## AuthService (`src/Auth/AuthService.php`)

Оставить: `login`, `logout`, `me`, `requestPasswordReset` (forgot), `resetPassword`
(`findActiveByEmail` → `find`).
Перенести реализацию из ProfileService: `register`, `confirmRegistration`, `resendConfirmation`
+ приватные `confirmationRequired`, `sendConfirmation`. Используют `UserService`
(normalizeEmail/assertEmailAvailable/generateDisplayName/create/update/find/getData) и `CUser::Register/Authorize`.
Убрать: `getCaptcha`, `updateProfile`, `requestPasswordChange`, делегат `registerByEmail`,
свойство `$profile` и создание ProfileService в конструкторе.
Конструктор: только `UserService`.

## ProfileService (`src/Auth/ProfileService.php`)

- `create(array $data): array` — переименованный `registerByEmail`; через `UserService`
  (create/update + хелперы) + `CUser::Authorize`. Возврат {id,login,email,name,phone,password}.
- `updateProfile(name='', phone=''): array` — перенести из AuthService; `UserService::update/getData`.
- `requestPasswordChange(): array` — перенести из AuthService; тонкая обёртка `CUser::SendPassword`
  на e-mail текущего пользователя (Profile не зависит от Auth).
- `deleteProfile(): array` — новый: `UserService::delete(GetID())` (деактивация) + `$USER->Logout()` → {deleted:true}.
Убрать: `register`, `confirm`, `resend` и их хелперы (уходят в Auth/User).

## Контроллеры (`www/local/controllers/`)

- `AuthController`: оставить login/register/confirmRegistration/resendConfirmation/logout/me/
  forgotPassword/resetPassword. Убрать captcha/updateProfile/passwordChange экшены и их
  configureActions-записи. Autowired DTO: Login/Register/ConfirmRegistration/ResetPassword.
- `ProfileController` (новый): `updateAction(string $name='', string $phone='')`,
  `passwordChangeAction()`, `deleteAction()`. Дефолтные фильтры ядра дают Authentication+Csrf;
  в configureActions — HttpMethod POST на каждый. init: `new ProfileService()`.
- `CaptchaController` (новый): `captchaAction(): array` → `(new CaptchaService())->generate()`.
  `getDefaultPreFilters()=[]` (публичный), configureActions captcha → HttpMethod GET.

## Маршруты (`routes/api.php`)

Auth (без изменений URL): login, register, register/confirm, register/resend, logout, me,
password/forgot, password/reset.
Captcha: `GET /api/auth/captcha` → `[CaptchaController, 'captcha']` (URL прежний).
Profile (новый неймспейс):
- `POST /api/profile/update` → `[ProfileController, 'update']`
- `POST /api/profile/password/change` → `[ProfileController, 'passwordChange']`
- `POST /api/profile/delete` → `[ProfileController, 'delete']`
Удалить: `/api/auth/profile/update`, `/api/auth/password/change`.

## DTO

Все Auth-DTO (`LoginRequest`, `RegisterRequest`, `ConfirmRegistrationRequest`, `ResetPasswordRequest`)
переносятся в подпапку `src/Auth/Dto/`, namespace `App\Auth\Dto`. Новых DTO нет:
updateProfile/delete/passwordChange — скаляры/без параметров.

## Swagger (`src/Swagger/Paths/Auth.php`)

- captcha: URL прежний, без изменений.
- updateProfile: path → `/api/profile/update`; passwordChange: path → `/api/profile/password/change`.
- добавить path `POST /api/profile/delete` (ответ {deleted:true}, auth+CSRF).
- login/register/confirm/resend/logout/me/forgot/reset: URL прежние.

## Docs

- `project-map.md`: новые ProfileController/CaptchaController, обновить AuthService/ProfileService/
  UserService/AuthController (строки, описания), маршруты.
- `bitrix-api-notes.md`: регистрация теперь в AuthService; `create` в ProfileService;
  deleteProfile — мягкое удаление (деактивация ACTIVE='N', без CUser::Delete); капча через CaptchaController.

## Проверка

- `php -l` батч по всем изменённым/новым файлам в контейнере.
- e2e: существующий `captcha_test.py` (auth URL прежние) + новый profile-сценарий
  (login → update → password/change → delete → me недоступен). Тестовые пользователи
  создаются/удаляются `auth_test_setup.php`.
