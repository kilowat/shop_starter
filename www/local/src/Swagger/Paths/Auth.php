<?php

namespace App\Swagger\Paths;

use OpenApi\Spec as OA;

use App\Swagger\Schemas\ApiError;
use App\Swagger\Schemas\ErrorResponse;
use App\Swagger\Schemas\Auth\AuthResponse;
use App\Swagger\Schemas\SuccessResponse;

#[OA\Tag(name: 'Auth', description: 'Авторизация и восстановление пароля')]
final class Auth
{
    // POST /api/auth/login

    #[OA\Operation\Post(
        path: '/api/auth/login',
        operationId: 'authLogin',
        summary: 'Вход по e-mail и паролю',
        description: 'Проверяет пару e-mail/пароль и авторизует пользователя. Капча на входе не запрашивается (опции безопасности групп по умолчанию не заданы).',
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
                                schema: new OA\Schema(type: 'string', minLength: 8, example: 'P@ssw0rd'),
                            ),
                            new OA\Property(
                                property: 'remember',
                                schema: new OA\Schema(type: 'boolean', default: true, description: 'Запомнить меня (cookie UIDH/UIDL)'),
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
                content: new OA\Schema(ref: SuccessResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Неверный e-mail или пароль',
                content: new OA\Schema(ref: ErrorResponse::class),
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
        summary: 'Регистрация по e-mail и паролю',
        description: 'Имя присваивается автоматически («Пользователь XXXXX»), логин равен e-mail. Если включена опция ядра new_user_registration_email_confirmation, пользователь создаётся неактивным, получает письмо NEW_USER_CONFIRM и требует подтверждения; иначе сразу авторизуется. Капча обязательна только при включённой опции ядра captcha_registration.',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        required: ['email', 'password', 'confirm_password'],
                        properties: [
                            new OA\Property(
                                property: 'email',
                                schema: new OA\Schema(type: 'string', format: 'email', example: 'ivanov@example.com'),
                            ),
                            new OA\Property(
                                property: 'password',
                                schema: new OA\Schema(type: 'string', minLength: 8, example: 'P@ssw0rd'),
                            ),
                            new OA\Property(
                                property: 'confirm_password',
                                schema: new OA\Schema(type: 'string', example: 'P@ssw0rd', description: 'Повтор пароля'),
                            ),
                            new OA\Property(
                                property: 'captcha_word',
                                schema: new OA\Schema(type: 'string', example: 'a1b2c', description: 'Код с картинки; нужен при включённой опции main/captcha_registration'),
                            ),
                            new OA\Property(
                                property: 'captcha_sid',
                                schema: new OA\Schema(type: 'string', example: '0a1b2c3d4e5f', description: 'ID капчи из GET /api/captcha'),
                            ),
                        ],
                    ),
                ),
            ],
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Пользователь создан; при включённом подтверждении требуется код из письма',
                content: new OA\Schema(ref: SuccessResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса (невалидные данные, занятый e-mail, неверная капча)',
                content: new OA\Schema(ref: ErrorResponse::class),
            ),
        ],
    )]
    public function register(): void
    {
    }


    // POST /api/auth/confirm

    #[OA\Operation\Post(
        path: '/api/auth/confirm',
        operationId: 'authConfirm',
        summary: 'Подтвердить регистрацию',
        description: 'Проверяет код из письма NEW_USER_CONFIRM, активирует пользователя и авторизует его.',
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
                                schema: new OA\Schema(type: 'string', example: 'a1b2c3d4', description: 'Код подтверждения из письма'),
                            ),
                            new OA\Property(
                                property: 'remember',
                                schema: new OA\Schema(type: 'boolean', default: true, description: 'Запомнить меня'),
                            ),
                        ],
                    ),
                ),
            ],
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Пользователь активирован и авторизован',
                content: new OA\Schema(ref: SuccessResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Пользователь не найден, уже подтверждён или неверный код',
                content: new OA\Schema(ref: ErrorResponse::class),
            ),
        ],
    )]
    public function confirmRegistration(): void
    {
    }


    // POST /api/auth/logout

    #[OA\Operation\Post(
        path: '/api/auth/logout',
        operationId: 'authLogout',
        summary: 'Выход',
        description: 'Снимает авторизацию и удаляет сессионные и remember-me cookies. Требуется авторизация и CSRF-токен.',
        parameters: [
            new OA\Parameter(
                name: 'sessid',
                in: 'query',
                required: true,
                description: 'CSRF-токен; также принимается в form-data или заголовком X-Bitrix-Csrf-Token (JSON-тело ядро не читает)',
                schema: new OA\Schema(type: 'string', example: 'a1b2c3d4e5f6'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\Schema(ref: SuccessResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Неверный CSRF-токен; новый токен возвращается в errors[].customData.csrf',
                content: new OA\Schema(ref: ErrorResponse::class),
            ),
            new OA\Response(
                response: 401,
                description: 'Требуется авторизация',
                content: new OA\Schema(ref: ErrorResponse::class),
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
        description: 'Возвращает данные авторизованного пользователя.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\Schema(ref: AuthResponse::class),
            ),
            new OA\Response(
                response: 401,
                description: 'Требуется авторизация',
                content: new OA\Schema(ref: ErrorResponse::class),
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
        description: 'Отправляет письмо с контрольным словом (USER_PASS_REQUEST). Ответ одинаков для существующего и неизвестного e-mail. Если включена опция ядра main/captcha_restoring_password, запрос без капчи будет отклонён ядром.',
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
                description: 'Письмо отправлено (ответ нейтрален для неизвестного e-mail)',
                content: new OA\Schema(ref: SuccessResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса',
                content: new OA\Schema(ref: ErrorResponse::class),
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
                        required: ['email', 'checkword', 'password'],
                        properties: [
                            new OA\Property(
                                property: 'email',
                                schema: new OA\Schema(type: 'string', format: 'email', example: 'ivanov@example.com'),
                            ),
                            new OA\Property(
                                property: 'checkword',
                                schema: new OA\Schema(type: 'string', example: 'a1b2c3d4e5f6', description: 'Контрольное слово из письма'),
                            ),
                            new OA\Property(
                                property: 'password',
                                schema: new OA\Schema(type: 'string', minLength: 8, example: 'N3wP@ssw0rd'),
                            ),
                            new OA\Property(
                                property: 'password_confirm',
                                schema: new OA\Schema(type: 'string', nullable: true, description: 'Повтор пароля; если не передан — считается равным password'),
                            ),
                        ],
                    ),
                ),
            ],
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Пароль изменён',
                content: new OA\Schema(ref: SuccessResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Пользователь не найден, неверное контрольное слово или пароль не соответствует политике',
                content: new OA\Schema(ref: ErrorResponse::class),
            ),
        ],
    )]
    public function resetPassword(): void
    {
    }
}