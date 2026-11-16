<?php

declare(strict_types=1);

namespace App\Helper\Captcha;

/**
 * Генерация и проверка капчи ядра (CMain::CaptchaGetCode / CaptchaCheckCode).
 * Sid одноразовый: гасится при проверке.
 */
final class CaptchaService
{
    /**
     * @return array{captcha_sid: string, captcha_url: string}
     */
    public function generate(): array
    {
        global $APPLICATION;

        $sid = (string) $APPLICATION->CaptchaGetCode();

        return [
            'captcha_sid' => $sid,
            'captcha_url' => $this->url($sid),
        ];
    }

    public function check(string $word, string $sid): bool
    {
        global $APPLICATION;

        return (bool) $APPLICATION->CaptchaCheckCode($word, $sid);
    }

    public function url(string $sid): string
    {
        return '/bitrix/tools/captcha.php?captcha_sid=' . $sid;
    }
}
