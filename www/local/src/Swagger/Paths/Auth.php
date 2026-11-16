<?php

namespace App\Swagger\Paths;

use OpenApi\Spec as OA;

use App\Swagger\Schemas\ApiError;
use App\Swagger\Schemas\Auth\AuthResponse;
use App\Swagger\Schemas\Auth\AuthResultResponse;
use App\Swagger\Schemas\Auth\CaptchaResponse;

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
                                schema: new OA\Schema(type: 'string', description: 'Слово капчи; обязательно после 3 неудачных попыток входа'),
                            ),
                            new OA\Property(
                                property: 'captcha_sid',
                                schema: new OA\Schema(type: 'integer', description: 'ID капчи; приходит в errors[].customData ответа с кодом captcha_required'),
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
                description: 'Ошибка запроса. При превышении лимита попыток — errors[].code = captcha_required и errors[].customData{captcha_sid, captcha_url}',
                content: new OA\Schema(ref: ApiError::class),
            ),
        ],
    )]
    public function login(): void
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
                                schema: new OA\Schema(type: 'string', description: 'Слово капчи, если ядро её затребовало'),
                            ),
                            new OA\Property(
                                property: 'captcha_sid',
                                schema: new OA\Schema(type: 'integer', description: 'ID капчи'),
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
                                schema: new OA\Schema(type: 'string', description: 'Слово капчи, если ядро её затребовало'),
                            ),
                            new OA\Property(
                                property: 'captcha_sid',
                                schema: new OA\Schema(type: 'integer', description: 'ID капчи'),
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
