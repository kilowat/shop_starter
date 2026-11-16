<?php

namespace App\Swagger\Paths;

use OpenApi\Spec as OA;

use App\Swagger\Schemas\ApiError;
use App\Swagger\Schemas\Auth\AuthResponse;
use App\Swagger\Schemas\Auth\AuthResultResponse;
use App\Swagger\Schemas\Auth\CaptchaResponse;
use App\Swagger\Schemas\Auth\RegistrationResponse;

#[OA\Tag(name: 'Auth', description: 'Авторизация и восстановление пароля')]
final class Auth
{
    // POST /api/auth/login

    #[OA\Operation\Post(
        path: '/api/auth/login',
        operationId: 'authLogin',
        summary: 'Вход по e-mail и паролю',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        required: ['email', 'password'],
                        properties: [
                            new OA\Property(
                                property: 'email',
                                schema: new OA\Schema(type: 'string', format: 'email', example: 'ivanov@example.com'),
                            ),
                            new OA\Property(
                                property: 'password',
                                schema: new OA\Schema(type: 'string', example: 'P@ssw0rd'),
                            ),
                            new OA\Property(
                                property: 'remember',
                                schema: new OA\Schema(type: 'boolean', default: false, description: 'Запомнить меня'),
                            ),
                            new OA\Property(
                                property: 'captcha_word',
                                schema: new OA\Schema(type: 'string', example: 'a1b2c', description: 'Код с картинки; капча обязательна при каждом входе'),
                            ),
                            new OA\Property(
                                property: 'captcha_sid',
                                schema: new OA\Schema(type: 'string', example: '0a1b2c3d4e5f', description: 'ID капчи из GET /api/auth/captcha или errors[].customData ответа с кодом captcha_required'),
                            ),
                        ],
                    ),
                ),
            ],
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\Schema(ref: AuthResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса. Без валидной капчи — errors[].code = captcha_required и errors[].customData{captcha_sid, captcha_url}',
                content: new OA\Schema(ref: ApiError::class),
            ),
        ],
    )]
    public function login(): void
    {
    }


    // POST /api/auth/register

    #[OA\Operation\Post(
        path: '/api/auth/register',
        operationId: 'authRegister',
        summary: 'Регистрация по e-mail',
        description: 'Пароль генерируется случайно. Имя и телефон опциональны; без имени присваивается «Пользователь XXXXX». Подтверждение кодом из письма — только если включена опция ядра new_user_registration_email_confirmation; иначе пользователь сразу авторизуется.',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        required: ['email'],
                        properties: [
                            new OA\Property(
                                property: 'email',
                                schema: new OA\Schema(type: 'string', format: 'email', example: 'ivanov@example.com'),
                            ),
                            new OA\Property(
                                property: 'name',
                                schema: new OA\Schema(type: 'string', example: 'Иван Иванов', description: 'Необязательно; по умолчанию «Пользователь XXXXX»'),
                            ),
                            new OA\Property(
                                property: 'phone',
                                schema: new OA\Schema(type: 'string', example: '+79001234567', description: 'Необязательно'),
                            ),
                            new OA\Property(
                                property: 'remember',
                                schema: new OA\Schema(type: 'boolean', default: false, description: 'Запомнить меня (если подтверждение по e-mail не требуется)'),
                            ),
                            new OA\Property(
                                property: 'captcha_word',
                                schema: new OA\Schema(type: 'string', example: 'a1b2c', description: 'Код с картинки, если включена опция captcha_registration'),
                            ),
                            new OA\Property(
                                property: 'captcha_sid',
                                schema: new OA\Schema(type: 'string', example: '0a1b2c3d4e5f', description: 'ID капчи из GET /api/auth/captcha'),
                            ),
                        ],
                    ),
                ),
            ],
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Пользователь создан; подтверждение требуется только при включённой опции ядра',
                content: new OA\Schema(ref: RegistrationResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса',
                content: new OA\Schema(ref: ApiError::class),
            ),
        ],
    )]
    public function register(): void
    {
    }


    // POST /api/auth/register/confirm

    #[OA\Operation\Post(
        path: '/api/auth/register/confirm',
        operationId: 'authRegisterConfirm',
        summary: 'Подтвердить регистрацию',
        description: 'Проверяет код из письма, активирует пользователя и авторизует его. Капча обязательна.',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        required: ['email', 'code'],
                        properties: [
                            new OA\Property(
                                property: 'email',
                                schema: new OA\Schema(type: 'string', format: 'email', example: 'ivanov@example.com', description: 'E-mail, указанный при регистрации'),
                            ),
                            new OA\Property(
                                property: 'code',
                                schema: new OA\Schema(type: 'string', example: '123456'),
                            ),
                            new OA\Property(
                                property: 'remember',
                                schema: new OA\Schema(type: 'boolean', default: false, description: 'Запомнить меня'),
                            ),
                            new OA\Property(
                                property: 'captcha_word',
                                schema: new OA\Schema(type: 'string', example: 'a1b2c', description: 'Код с картинки; капча обязательна при каждом подтверждении'),
                            ),
                            new OA\Property(
                                property: 'captcha_sid',
                                schema: new OA\Schema(type: 'string', example: '0a1b2c3d4e5f', description: 'ID капчи из GET /api/auth/captcha или errors[].customData ответа с кодом captcha_required'),
                            ),
                        ],
                    ),
                ),
            ],
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\Schema(ref: AuthResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса. Без валидной капчи — errors[].code = captcha_required и errors[].customData{captcha_sid, captcha_url}',
                content: new OA\Schema(ref: ApiError::class),
            ),
        ],
    )]
    public function confirmRegistration(): void
    {
    }


    // POST /api/auth/register/resend

    #[OA\Operation\Post(
        path: '/api/auth/register/resend',
        operationId: 'authRegisterResend',
        summary: 'Повторить код подтверждения',
        description: 'Повторно отправляет код подтверждения на e-mail неподтверждённого пользователя. Ответ одинаков для существующего и неизвестного e-mail.',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        required: ['email'],
                        properties: [
                            new OA\Property(
                                property: 'email',
                                schema: new OA\Schema(type: 'string', format: 'email', example: 'ivanov@example.com'),
                            ),
                        ],
                    ),
                ),
            ],
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\Schema(ref: AuthResultResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса',
                content: new OA\Schema(ref: ApiError::class),
            ),
        ],
    )]
    public function resendConfirmation(): void
    {
    }


    // GET /api/auth/captcha

    #[OA\Operation\Get(
        path: '/api/auth/captcha',
        operationId: 'authCaptcha',
        summary: 'Новая капча для формы входа',
        description: 'Возвращает свежий ID капчи и URL картинки; используется для обновления капчи.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\Schema(ref: CaptchaResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса',
                content: new OA\Schema(ref: ApiError::class),
            ),
        ],
    )]
    public function captcha(): void
    {
    }


    // POST /api/auth/logout

    #[OA\Operation\Post(
        path: '/api/auth/logout',
        operationId: 'authLogout',
        summary: 'Выход',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\Schema(ref: AuthResultResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса',
                content: new OA\Schema(ref: ApiError::class),
            ),
        ],
    )]
    public function logout(): void
    {
    }


    // GET /api/auth/me

    #[OA\Operation\Get(
        path: '/api/auth/me',
        operationId: 'authMe',
        summary: 'Текущий пользователь',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\Schema(ref: AuthResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса',
                content: new OA\Schema(ref: ApiError::class),
            ),
        ],
    )]
    public function me(): void
    {
    }


    // POST /api/auth/password/forgot

    #[OA\Operation\Post(
        path: '/api/auth/password/forgot',
        operationId: 'authPasswordForgot',
        summary: 'Запросить восстановление пароля',
        description: 'Отправляет письмо с контрольным словом на указанный e-mail. Ответ одинаков для существующего и неизвестного e-mail.',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        required: ['email'],
                        properties: [
                            new OA\Property(
                                property: 'email',
                                schema: new OA\Schema(type: 'string', format: 'email', example: 'ivanov@example.com'),
                            ),
                            new OA\Property(
                                property: 'captcha_word',
                                schema: new OA\Schema(type: 'string', example: 'a1b2c', description: 'Код с картинки, если ядро её затребовало'),
                            ),
                            new OA\Property(
                                property: 'captcha_sid',
                                schema: new OA\Schema(type: 'string', example: '0a1b2c3d4e5f', description: 'ID капчи из GET /api/auth/captcha'),
                            ),
                        ],
                    ),
                ),
            ],
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\Schema(ref: AuthResultResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса',
                content: new OA\Schema(ref: ApiError::class),
            ),
        ],
    )]
    public function forgotPassword(): void
    {
    }


    // POST /api/auth/password/reset

    #[OA\Operation\Post(
        path: '/api/auth/password/reset',
        operationId: 'authPasswordReset',
        summary: 'Установить новый пароль',
        description: 'Устанавливает новый пароль по e-mail и контрольному слову из письма восстановления.',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        required: ['email', 'password', 'checkword'],
                        properties: [
                            new OA\Property(
                                property: 'email',
                                schema: new OA\Schema(type: 'string', format: 'email', example: 'ivanov@example.com'),
                            ),
                            new OA\Property(
                                property: 'password',
                                schema: new OA\Schema(type: 'string', example: 'N3wP@ssw0rd'),
                            ),
                            new OA\Property(
                                property: 'checkword',
                                schema: new OA\Schema(type: 'string', example: 'a1b2c3d4e5f6'),
                            ),
                            new OA\Property(
                                property: 'password_confirm',
                                schema: new OA\Schema(type: 'string', description: 'Повтор пароля; если пусто — совпадает с password'),
                            ),
                            new OA\Property(
                                property: 'captcha_word',
                                schema: new OA\Schema(type: 'string', example: 'a1b2c', description: 'Код с картинки, если ядро её затребовало'),
                            ),
                            new OA\Property(
                                property: 'captcha_sid',
                                schema: new OA\Schema(type: 'string', example: '0a1b2c3d4e5f', description: 'ID капчи из GET /api/auth/captcha'),
                            ),
                        ],
                    ),
                ),
            ],
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\Schema(ref: AuthResultResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса',
                content: new OA\Schema(ref: ApiError::class),
            ),
        ],
    )]
    public function resetPassword(): void
    {
    }
}
