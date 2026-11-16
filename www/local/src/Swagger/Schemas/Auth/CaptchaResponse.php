<?php

namespace App\Swagger\Schemas\Auth;

use OpenApi\Spec as OA;

#[OA\Schema]
final class CaptchaResponse
{
    #[OA\Property]
    #[OA\Schema(type: 'string', description: 'ID капчи для captcha_sid', example: '0a1b2c3d4e5f')]
    public string $captcha_sid;

    #[OA\Property]
    #[OA\Schema(type: 'string', description: 'URL картинки капчи', example: '/bitrix/tools/captcha.php?captcha_sid=0a1b2c3d4e5f')]
    public string $captcha_url;
}