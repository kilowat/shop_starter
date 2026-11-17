<?php

namespace App\Swagger\Paths;

use OpenApi\Spec as OA;

use App\Swagger\Schemas\ErrorResponse;
use App\Swagger\Schemas\SuccessResponse;

#[OA\Tag(name: 'Profile', description: 'Профиль текущего пользователя')]
final class Profile
{
    // POST /api/profile/update

    #[OA\Operation\Post(
        path: '/api/profile/update',
        operationId: 'profileUpdate',
        summary: 'Обновить профиль',
        description: 'Обновляет имя и/или телефон текущего пользователя. Поля опциональны: непереданные не изменяются. Телефон сохраняется в PERSONAL_PHONE и валидируется как номер. Требуются авторизация и CSRF-токен.',
        parameters: [
            new OA\Parameter(
                name: 'sessid',
                in: 'query',
                required: true,
                description: 'CSRF-токен; также принимается в form-data или заголовком X-Bitrix-Csrf-Token (JSON-тело ядро не читает)',
                schema: new OA\Schema(type: 'string', example: 'a1b2c3d4e5f6'),
            ),
        ],
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
                                schema: new OA\Schema(type: 'string', nullable: true, example: 'Иван Иванов', description: 'Имя (USER.NAME); null/отсутствие — не менять'),
                            ),
                            new OA\Property(
                                property: 'phone',
                                schema: new OA\Schema(type: 'string', nullable: true, example: '+79001234567', description: 'Телефон (USER.PERSONAL_PHONE); null/отсутствие — не менять'),
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
                description: 'Ошибка запроса (невалидный телефон, неверный CSRF-токен, ошибка CUser::Update)',
                content: new OA\Schema(ref: ErrorResponse::class),
            ),
            new OA\Response(
                response: 401,
                description: 'Требуется авторизация',
                content: new OA\Schema(ref: ErrorResponse::class),
            ),
        ],
    )]
    public function updateProfile(): void
    {
    }


    // POST /api/profile/delete

    #[OA\Operation\Post(
        path: '/api/profile/delete',
        operationId: 'profileDelete',
        summary: 'Удалить профиль',
        description: 'Мягкое удаление: деактивирует текущего пользователя (ACTIVE=N) и выполняет выход. Запись физически не удаляется. Требуются авторизация и CSRF-токен.',
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
                description: 'Ошибка запроса (неверный CSRF-токен, ошибка деактивации)',
                content: new OA\Schema(ref: ErrorResponse::class),
            ),
            new OA\Response(
                response: 401,
                description: 'Требуется авторизация',
                content: new OA\Schema(ref: ErrorResponse::class),
            ),
        ],
    )]
    public function deleteProfile(): void
    {
    }

}