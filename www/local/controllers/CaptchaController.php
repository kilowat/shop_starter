<?php

namespace Controllers;

use App\Helper\Captcha\CaptchaService;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;

final class CaptchaController extends Controller
{
    protected function getDefaultPreFilters(): array
    {
        return [];
    }

    public function configureActions(): array
    {
        return [
            'captcha' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
                ],
            ],
        ];
    }

    /**
     * Новая капча для форм входа/регистрации.
     */
    public function captchaAction(): array
    {
        return (new CaptchaService())->generate();
    }
}
