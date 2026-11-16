<?php

namespace App\Swagger\Schemas\Auth;

use OpenApi\Spec as OA;

#[OA\Schema]
final class CaptchaResponse
{
    #[OA\Property(example: '0a1b2c3d4e5f')]
    public string $captcha_sid;

    #[OA\Property(example: '/bitrix/tools/captcha.php?captcha_sid=0a1b2c3d4e5f')]
    public string $captcha_url;
}