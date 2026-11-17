<?php

namespace Controllers;

use App\Auth\AuthService;
use App\Auth\Dto\ConfirmRegistrationRequest;
use App\Auth\Dto\ForgotPasswordRequest;
use App\Auth\Dto\LoginRequest;
use App\Auth\Dto\RegisterRequest;
use App\Auth\Dto\ResendConfirmationRequest;
use App\Auth\Dto\ResetPasswordRequest;
use App\Helper\Captcha\CaptchaActionFilter;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Validation\Engine\AutoWire\ValidationParameter;

final class AuthController extends Controller
{
    private const REQUEST_DTO = [
        LoginRequest::class,
        RegisterRequest::class,
        ConfirmRegistrationRequest::class,
        ResendConfirmationRequest::class,
        ForgotPasswordRequest::class,
        ResetPasswordRequest::class,
    ];

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
        $captcha = [new CaptchaActionFilter()];

        return [
            'login' => ['prefilters' => $captcha],
            'register' => ['prefilters' => $captcha],
            'confirmRegistration' => ['prefilters' => $captcha],
            'forgotPassword' => ['prefilters' => $captcha],
            'resetPassword' => ['prefilters' => $captcha],
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
        return array_map(
            fn(string $dto) => new ValidationParameter(
                $dto,
                fn() => $dto::fromRequest($this->getRequest()),
            ),
            self::REQUEST_DTO,
        );
    }

    /**
     * Вход по e-mail и паролю.
     */
    public function loginAction(LoginRequest $request): void
    {
        $this->auth->login($request);
    }

    /**
     * Регистрация по e-mail и паролю.
     * Подтверждение по коду из письма — если включена опция ядра.
     */
    public function registerAction(RegisterRequest $request): void
    {
        $this->auth->register($request);
    }

    /**
     * Подтверждение регистрации кодом из письма.
     */
    public function confirmRegistrationAction(ConfirmRegistrationRequest $request): void
    {
        $this->auth->confirmRegistration($request);
    }

    /**
     * Повторная отправка кода подтверждения.
     */
    public function resendConfirmationAction(ResendConfirmationRequest $request): void
    {
        $this->auth->resendConfirmation($request);
    }

    /**
     * Выход.
     */
    public function logoutAction(): void
    {
        $this->auth->logout();
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
    public function forgotPasswordAction(ForgotPasswordRequest $request): void
    {
        $this->auth->requestPasswordReset($request);
    }

    /**
     * Установить новый пароль по контрольному слову из письма.
     */
    public function resetPasswordAction(ResetPasswordRequest $request): void
    {
        $this->auth->resetPassword($request);
    }
}