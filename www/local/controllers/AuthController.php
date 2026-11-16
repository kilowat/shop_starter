<?php

namespace Controllers;

use App\Auth\CaptchaActionFilter;
use App\Auth\AuthService;
use App\Auth\ForgotPasswordRequest;
use App\Auth\LoginRequest;
use App\Auth\ResetPasswordRequest;


use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Validation\Engine\AutoWire\ValidationParameter;

final class AuthController extends Controller
{
    private AuthService $auth;

    protected function init(): void
    {
        parent::init();

        $this->auth = new AuthService();
    }

    protected function getDefaultPreFilters(): array
    {
        return [];
    }

    public function configureActions(): array
    {
        return [
            'login' => [
                'prefilters' => [
                    new CaptchaActionFilter(),
                ],
            ],
            'logout' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'me' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
                ],
            ],
        ];
    }

    public function getAutoWiredParameters(): array
    {
        return [
            new ValidationParameter(
                LoginRequest::class,
                fn() => LoginRequest::fromRequest($this->getRequest()),
            ),
            new ValidationParameter(
                ForgotPasswordRequest::class,
                fn() => ForgotPasswordRequest::fromRequest($this->getRequest()),
            ),
            new ValidationParameter(
                ResetPasswordRequest::class,
                fn() => ResetPasswordRequest::fromRequest($this->getRequest()),
            ),
        ];
    }

    /**
     * Вход по e-mail и паролю.
     */
    public function loginAction(LoginRequest $request): array
    {
        return $this->auth->login($request);
    }

    /**
     * Новая капча для формы входа.
     */
    public function captchaAction(): array
    {
        return $this->auth->getCaptcha();
    }

    /**
     * Выход.
     */
    public function logoutAction(): array
    {
        return $this->auth->logout();
    }

    /**
     * Текущий пользователь.
     */
    public function meAction(): array
    {
        return $this->auth->me();
    }

    /**
     * Отправить письмо для восстановления пароля.
     */
    public function forgotPasswordAction(ForgotPasswordRequest $request): array
    {
        return $this->auth->requestPasswordReset($request);
    }

    /**
     * Установить новый пароль по контрольному слову из письма.
     */
    public function resetPasswordAction(ResetPasswordRequest $request): array
    {
        return $this->auth->resetPassword($request);
    }
}
