<?php

namespace Controllers;

use App\Auth\AuthService;
use App\Auth\Dto\ConfirmRegistrationRequest;
use App\Auth\Dto\LoginRequest;
use App\Auth\Dto\RegisterRequest;
use App\Auth\Dto\ResetPasswordRequest;
use App\Helper\Captcha\CaptchaActionFilter;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Validation\Engine\AutoWire\ValidationParameter;

final class AuthController extends Controller
{
    private AuthService $auth;

    protected function init(): void
    {
        $this->auth = new AuthService();

        parent::init();
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
            'confirmRegistration' => [
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
                RegisterRequest::class,
                fn() => RegisterRequest::fromRequest($this->getRequest()),
            ),
            new ValidationParameter(
                ConfirmRegistrationRequest::class,
                fn() => ConfirmRegistrationRequest::fromRequest($this->getRequest()),
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
     * Регистрация по e-mail: имя и телефон опциональны, пароль генерируется случайно.
     * Подтверждение по коду из письма — если включена опция ядра.
     */
    public function registerAction(RegisterRequest $request): array
    {
        return $this->auth->register($request);
    }

    /**
     * Подтверждение регистрации кодом из письма.
     */
    public function confirmRegistrationAction(ConfirmRegistrationRequest $request): array
    {
        return $this->auth->confirmRegistration($request);
    }

    /**
     * Повторная отправка кода подтверждения.
     */
    public function resendConfirmationAction(string $email): array
    {
        return $this->auth->resendConfirmation($email);
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
    public function forgotPasswordAction(string $email, string $captcha_word = '', string $captcha_sid = ''): array
    {
        return $this->auth->requestPasswordReset($email, $captcha_word, $captcha_sid);
    }

    /**
     * Установить новый пароль по контрольному слову из письма.
     */
    public function resetPasswordAction(ResetPasswordRequest $request): array
    {
        return $this->auth->resetPassword($request);
    }
}
