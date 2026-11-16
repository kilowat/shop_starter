<?php

namespace App\Swagger\Paths;

use OpenApi\Spec as OA;

use App\Swagger\Schemas\ApiError;
use App\Swagger\Schemas\Auth\CaptchaResponse;


#[OA\Tag(name: 'Captcha', description: 'Captcha')]
final class Captcha
{
    // GET /api/auth/captcha

    #[OA\Operation\Get(
        path: '/api/captcha',
        operationId: 'Captcha',
        summary: 'Новая капча',
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
}
