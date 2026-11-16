<?php

namespace App\Swagger\Paths;

use OpenApi\Spec as OA;

use App\Swagger\Schemas\ApiError;
use App\Swagger\Schemas\Auth\AuthResponse;
use App\Swagger\Schemas\Auth\AuthResultResponse;

#[OA\Tag(name: 'Profile', description: 'Профиль текущего пользователя')]
final class Profile
{
    // POST /api/profile/update

    #[OA\Operation\Post(
        path: '/api/profile/update',
        operationId: 'profileUpdate',
        summary: 'Обновить профиль',
        description: 'Обновляет имя и/или телефон текущего пользователя. Требуется авторизация.',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(
                                property: 'name',
                                schema: new OA\Schema(type: 'string', example: 'Иван Иванов'),
                            ),
                            new OA\Property(
                                property: 'phone',
                                schema: new OA\Schema(type: 'string', example: '+79001234567'),
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
                description: 'Ошибка запроса',
                content: new OA\Schema(ref: ApiError::class),
            ),
        ],
    )]
    public function updateProfile(): void
    {
    }


    // POST /api/profile/password/change

    #[OA\Operation\Post(
        path: '/api/profile/password/change',
        operationId: 'profilePasswordChange',
        summary: 'Запросить смену пароля',
        description: 'Отправляет письмо с контрольным словом на e-mail текущего пользователя. Новый пароль устанавливается через POST /api/auth/password/reset. Требуется авторизация.',
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
    public function passwordChange(): void
    {
    }


    // POST /api/profile/delete

    #[OA\Operation\Post(
        path: '/api/profile/delete',
        operationId: 'profileDelete',
        summary: 'Удалить профиль',
        description: 'Мягкое удаление: деактивирует текущего пользователя (ACTIVE=N) и выполняет выход. Запись физически не удаляется. Требуется авторизация.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'deleted', schema: new OA\Schema(type: 'boolean', example: true)),
                    ],
                ),
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса',
                content: new OA\Schema(ref: ApiError::class),
            ),
        ],
    )]
    public function deleteProfile(): void
    {
    }
}