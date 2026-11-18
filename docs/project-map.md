# Карта проекта shop_starter

> Сгенерировано `scripts/project-map.php`. Пересобрать после структурных изменений:
> `php scripts/project-map.php`. Читать этот файл вместо обхода `www/bitrix/**`.

Масштаб: `www/bitrix` — 40 196 PHP-файлов (~1.6 ГБ, только чтение), `www/local` — 87 PHP-файлов нашего кода (без vendor).

## Точки входа

- `www/local/init.php` — автозагрузка Composer, `src/const.php`, `src/event.php`, `src/func.php`
- `www/local/routes/*.php` — маршруты D7 (`api.php`, `web.php`, `swagger.php`)
- `www/local/controllers/*.php` — контроллеры API
- `www/local/templates/shop/` — шаблон сайта (`header.php`, `footer.php`, `components/bitrix/**`)

## Маршруты

- GET /api/basket → BasketController::show (www/local/routes/api.php)
- POST /api/basket/items → BasketController::add (www/local/routes/api.php)
- POST /api/basket/items/quantity → BasketController::updateQuantity (www/local/routes/api.php)
- POST /api/basket/items/delay → BasketController::setDelay (www/local/routes/api.php)
- POST /api/basket/items/remove → BasketController::remove (www/local/routes/api.php)
- POST /api/basket/clear → BasketController::clear (www/local/routes/api.php)
- POST /api/basket/coupon → BasketController::applyCoupon (www/local/routes/api.php)
- POST /api/basket/coupon/remove → BasketController::removeCoupon (www/local/routes/api.php)
- POST /api/auth/login → AuthController::login (www/local/routes/api.php)
- POST /api/auth/register → AuthController::register (www/local/routes/api.php)
- POST /api/auth/confirm → AuthController::confirmRegistration (www/local/routes/api.php)
- POST /api/auth/logout → AuthController::logout (www/local/routes/api.php)
- GET /api/auth/me → AuthController::me (www/local/routes/api.php)
- POST /api/auth/password/forgot → AuthController::forgotPassword (www/local/routes/api.php)
- POST /api/auth/password/reset → AuthController::resetPassword (www/local/routes/api.php)
- GET /api/captcha → CaptchaController::captcha (www/local/routes/api.php)
- POST /api/profile/update → ProfileController::update (www/local/routes/api.php)
- POST /api/profile/delete → ProfileController::delete (www/local/routes/api.php)

## Компоненты проекта

- www/local/components/shop/catalog.sort (класс: www/local/components/shop/catalog.sort/class.php, шаблоны: —)

## Переопределённые шаблоны компонентов

- www/local/templates/shop/components/bitrix/breadcrumb
- www/local/templates/shop/components/bitrix/catalog.section
- www/local/templates/shop/components/bitrix/catalog.smart.filter
- www/local/templates/shop/components/bitrix/menu
- www/local/templates/shop/components/bitrix/system.pagenavigation

## Файлы `www/local`

| Файл | Строк | Символы | Назначение |
| --- | --- | --- | --- |
| `www/local/.settings_extra.php` | 35 | — | — |
| `www/local/components/shop/catalog.sort/.description.php` | 19 | — | Сортировка каталога: серверный <select>, состояние — в URL и в |
| `www/local/components/shop/catalog.sort/.parameters.php` | 43 | — | Параметры компонента сортировки. |
| `www/local/components/shop/catalog.sort/class.php` | 167 | class CatalogSortComponent | Компонент сортировки каталога. |
| `www/local/components/shop/catalog.sort/templates/.default/template.php` | 43 | — | Шаблон компонента сортировки: обычный <select>, выбранное значение |
| `www/local/controllers/AuthController.php` | 157 | class AuthController | Вход (e-mail/пароль), регистрация (+confirm), выход, me, восстановление пароля (forgot/reset). DTO через getAutoWiredParameters: Login/Register/Confirm/Reset/Email; forgot — EmailRequest. Капча в login/confirm не проверяется; register передаёт captcha_word/sid в CUser::Register (по опции ядра). Write-экшены void, данные пользователя — только me. |
| `www/local/controllers/BasketController.php` | 121 | class BasketController | Получить корзину. |
| `www/local/controllers/CaptchaController.php` | 34 | class CaptchaController | GET капчи (CaptchaService::generate). |
| `www/local/controllers/ProfileController.php` | 67 | class ProfileController | Профиль: update (ProfilePayload::toFields() → NAME/PERSONAL_PHONE), delete (мягкое удаление через ProfileService::deactive). Все экшены void. |
| `www/local/include/components/catalog_filter.php` | 41 | — | Смарт-фильтр каталога — объявление bitrix:catalog.smart.filter (шаблон signa). |
| `www/local/include/components/catalog_section.php` | 80 | — | Список товаров каталога — объявление bitrix:catalog.section (шаблон shop_bootstra |
| `www/local/include/components/catalog_sort.php` | 40 | — | Сортировка каталога — объявление shop:catalog.sort. |
| `www/local/init.php` | 8 | — | — |
| `www/local/routes/api.php` | 34 | — | — |
| `www/local/routes/swagger.php` | 8 | — | — |
| `www/local/routes/web.php` | 11 | — | — |
| `www/local/src/Auth/AuthService.php` | 145 | class AuthService | Аутентификация: вход, выход, регистрация через CUser::Register (авто-имя, письма и авто-вход — ядро), confirm (Main\UserTable + ACTIVE/CONFIRM_CODE), forgot/reset пароля. Операции void, кроме me (в контроллере). |
| `www/local/src/Auth/Dto/ConfirmRegistrationRequest.php` | 24 | class ConfirmRegistrationRequest | DTO подтверждения регистрации (email, code, remember). |
| `www/local/src/Auth/Dto/EmailRequest.php` | 21 | class EmailRequest | DTO e-mail для forgot (email, Trim). |
| `www/local/src/Auth/Dto/LoginRequest.php` | 24 | class LoginRequest | DTO логина (email, password, remember). |
| `www/local/src/Auth/Dto/ProfilePayload.php` | 26 | class ProfilePayload | DTO профиля (name → NAME, phone → PERSONAL_PHONE); nullable, toFields(). |
| `www/local/src/Auth/Dto/RegisterRequest.php` | 27 | class RegisterRequest | DTO регистрации (email, password, confirm_password). |
| `www/local/src/Auth/Dto/ResetPasswordRequest.php` | 30 | class ResetPasswordRequest | DTO установки нового пароля (email, checkword, password, password_confirm). |
| `www/local/src/Auth/ProfileService.php` | 64 | class ProfileService | Профиль: update (CUser::Update по toFields()), get (CurrentUser), deactive (ACTIVE=N + Logout) — операции void. |
| `www/local/src/Basket/BasketRequest.php` | 22 | class BasketRequest | — |
| `www/local/src/Basket/BasketService.php` | 1018 | class BasketService | Добавить товар в корзину. |
| `www/local/src/Helper/Application.php` | 120 | class Application | — |
| `www/local/src/Helper/Asset.php` | 161 | class Asset | Публикует изображение шаблона с конвертацией в webp. |
| `www/local/src/Helper/CFileWebp.php` | 250 | class CFileWebp | Наследник CFile с поддержкой ресайза в WebP. |
| `www/local/src/Helper/DataStorage.php` | 36 | class DataStorage | РљР»Р°СЃСЃ-С…СЂР°РЅРёР»РёС‰Рµ РґР»СЏ РїРµСЂРµРґР°С‡Рё РґР°РЅРЅС‹С… РјРµР¶РґСѓ РєРѕРјРїРѕРЅРµРЅС‚Р°РјРё РІ СЂР°РјРєР°С… Р |
| `www/local/src/Helper/Debug.php` | 226 | class Debug | Определяет файл и строку реального вызова dump(). |
| `www/local/src/Helper/File.php` | 64 | class File | — |
| `www/local/src/Helper/Captcha/CaptchaActionFilter.php` | 57 | class CaptchaActionFilter | Prefilter: требует валидную капчу (captcha_word/captcha_sid) в каждом POST обёрнутого экшена, без счётчиков. |
| `www/local/src/Helper/Captcha/CaptchaService.php` | 39 | class CaptchaService | Генерация/проверка капчи ядра (generate/check/url), sid одноразовый. |
| `www/local/src/Helper/Http/Mapper/Cast.php` | 24 | enum Cast | — |
| `www/local/src/Helper/Http/Mapper/Field.php` | 16 | class Field | — |
| `www/local/src/Helper/Http/Mapper/Key.php` | 15 | class Key | — |
| `www/local/src/Helper/Http/Mapper/RequestMapper.php` | 346 | trait RequestMapper | Создаёт DTO и заполняет его данными HTTP-запроса. Обязательность неявная: не-nullable параметр без дефолта; не передан/null → BinderArgumentException с ошибками по ключам полей. |
| `www/local/src/Helper/Http/Mapper/RequestMethod.php` | 29 | enum RequestMethod | Нормализация списка методов (enum или строки) в массив строк. |
| `www/local/src/Helper/Http/Validator/ForMethodsValidator.php` | 41 | class ForMethodsValidator | Декоратор: выполняет вложенный валидатор только для указанных HTTP |
| `www/local/src/Helper/Http/Validator/NotEmptyOn.php` | 56 | class NotEmptyOn | Штатный NotEmpty, но только для указанных HTTP-методов. |
| `www/local/src/Helper/Http/Validator/UploadFile.php` | 44 | class UploadFile | — |
| `www/local/src/Helper/Http/Validator/UploadFileValidator.php` | 138 | class UploadFileValidator | @param int/null $maxSize      максимальный размер одного файла в байтах |
| `www/local/src/Helper/Image.php` | 373 | class Image | — |
| `www/local/src/Helper/Localization.php` | 498 | class Localization | @property \Bitrix\Main\ORM\Data\DataManager $dataClass |
| `www/local/src/Helper/Localization/Admin.php` | 24 | class Admin | — |
| `www/local/src/Helper/PagePropertyFilter.php` | 54 | class PagePropertyFilter | — |
| `www/local/src/Helper/Seo.php` | 210 | class Seo | OG из элемента (override) |
| `www/local/src/Helper/Slot.php` | 27 | class Slot | — |
| `www/local/src/Swagger/Paths/Auth.php` | 335 | class Auth | OpenAPI-описание эндпоинтов /api/auth/* (login, register, confirm, logout, me, forgot, reset). |
| `www/local/src/Swagger/Paths/Basket.php` | 348 | class Basket | — |
| `www/local/src/Swagger/Paths/Profile.php` | 109 | class Profile | OpenAPI-описание эндпоинтов /api/profile/* (update, delete). |
| `www/local/src/Swagger/Schemas/Api.php` | 29 | class Api, class ErrorResponse | Тег OpenAPI/Info и схема конверта ошибки {status, data, errors}. |
| `www/local/src/Swagger/Schemas/Auth/AuthResponse.php` | 24 | class AuthResponse | Схема ответа с данными пользователя (GET /api/auth/me). |
| `www/local/src/Swagger/Schemas/Auth/AuthUser.php` | 24 | class AuthUser | Схема пользователя API (id, name, full_name, email). |
| `www/local/src/Swagger/Schemas/ApiError.php` | 28 | class ApiError | Ошибка API (code, message, customData). |
| `www/local/src/Swagger/Schemas/SuccessResponse.php` | 28 | class SuccessResponse | Успешный ответ без данных (data: null) для write-операций Auth/Profile. |
| `www/local/src/Swagger/Schemas/Basket/Basket.php` | 28 | class Basket | — |
| `www/local/src/Swagger/Schemas/Basket/BasketCoupon.php` | 20 | class BasketCoupon | — |
| `www/local/src/Swagger/Schemas/Basket/BasketItem.php` | 32 | class BasketItem | — |
| `www/local/src/Swagger/Schemas/Basket/BasketResponse.php` | 24 | class BasketResponse | — |
| `www/local/src/Swagger/Schemas/Basket/BasketSummary.php` | 28 | class BasketSummary | — |
| `www/local/src/Swagger/router.php` | 149 | — | — |
| `www/local/src/Swagger/ui/index.php` | 38 | — | — |
| `www/local/src/const.php` | 4 | class K | — |
| `www/local/src/event.php` | 2 | — | — |
| `www/local/src/func.php` | 282 | — | Регистрация контроллера в роуте  в любом неймспейсе |
| `www/local/templates/shop/.styles.php` | 3 | — | — |
| `www/local/templates/shop/components/bitrix/breadcrumb/bootsrap_breadcrumbs/template.php` | 47 | — | Хлебные крошки на Bootstrap 5. |
| `www/local/templates/shop/components/bitrix/catalog.section/shop_bootstrap/.parameters.php` | 11 | — | Параметры шаблона списка — редактируются в визуальном редакторе |
| `www/local/templates/shop/components/bitrix/catalog.section/shop_bootstrap/component_epilog.php` | 13 | — | Ассеты умного фильтра: ползунок noUiSlider нужен только этому шаблону. |
| `www/local/templates/shop/components/bitrix/catalog.section/shop_bootstrap/result_modifier.php` | 19 | — | Подготовка данных компонента под свой шаблон. |
| `www/local/templates/shop/components/bitrix/catalog.section/shop_bootstrap/template.php` | 81 | — | Список товаров (bitrix:catalog.section), Bootstrap 5. |
| `www/local/templates/shop/components/bitrix/catalog.smart.filter/signa/.parameters.php` | 11 | — | Параметры шаблона фильтра — редактируются в визуальном редактор |
| `www/local/templates/shop/components/bitrix/catalog.smart.filter/signa/ajax.php` | 28 | — | AJAX-ответ смарт-фильтра (компонент сам включает этот шаблон при ajax= |
| `www/local/templates/shop/components/bitrix/catalog.smart.filter/signa/component_epilog.php` | 18 | — | Ассеты умного фильтра: ползунок noUiSlider нужен только этому шаблону. |
| `www/local/templates/shop/components/bitrix/catalog.smart.filter/signa/result_modifier.php` | 39 | — | Данные умного фильтра для шаблона signa. |
| `www/local/templates/shop/components/bitrix/catalog.smart.filter/signa/template.php` | 125 | — | Умный фильтр каталога. |
| `www/local/templates/shop/components/bitrix/menu/shop_nav/template.php` | 22 | — | Шаблон меню шапки (bitrix:menu) на Bootstrap 5. |
| `www/local/templates/shop/components/bitrix/system.pagenavigation/shop_bootstrap/template.php` | 134 | — | Постраничная навигация (bitrix:system.pagenavigation) на Bootstrap 5. |
| `www/local/templates/shop/description.php` | 8 | — | — |
| `www/local/templates/shop/footer.php` | 25 | — | Подвал шаблона shop. |
| `www/local/templates/shop/header.php` | 89 | — | — |

## Методы контроллеров

- `www/local/controllers/AuthController.php` — configureActions:36, getAutoWiredParameters:55, loginAction:84, registerAction:96, logoutAction:113, meAction:121, confirmRegistrationAction:135, forgotPasswordAction:144, resetPasswordAction:149
- `www/local/controllers/CaptchaController.php` — configureActions:16, captchaAction:30
- `www/local/controllers/ProfileController.php` — configureActions:30, getAutoWiredParameters:44, updateAction:57, deleteAction:64
- `www/local/controllers/BasketController.php` — configureActions:22, getAutoWiredParameters:27, showAction:40, addAction:48, updateQuantityAction:58, setDelayAction:68, removeAction:78, clearAction:88, applyCouponAction:100, removeCouponAction:112

