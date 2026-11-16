<?php

namespace Controllers;

use App\Auth\CaptchaActionFilter;
use App\Auth\AuthService;
use App\Auth\LoginRequest;
use App\Auth\Registration\ConfirmRegistrationRequest;
use App\Auth\Registration\RegisterRequest;
use App\Auth\ResetPasswordRequest;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Validation\Engine\AutoWire\ValidationParameter;

final class AuthController extends Controller
{
    private AuthService $auth;
    private CaptchaActionFilter $loginCaptcha;
    private CaptchaActionFilter $confirmCaptcha;

    protected function init(): void
    {
        $this->auth = new AuthService();
        $this->loginCaptcha = new CaptchaActionFilter();
        $this->confirmCaptcha = new CaptchaActionFilter(attemptsLimit: 5);

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
                    $this->loginCaptcha,
                ],
                'postfilters' => [
                    $this->loginCaptcha,
                ],
            ],
            'confirmRegistration' => [
                'prefilters' => [
                    $this->confirmCaptcha,
                ],
                'postfilters' => [
                    $this->confirmCaptcha,
                ],
            ],
            'resendConfirmation' => [
                'postfilters' => [
                    $this->confirmCaptcha,
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
            'updateProfile' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'passwordChange' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
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
     * Обновить имя и/или телефон текущего пользователя.
     */
    public function updateProfileAction(string $name = '', string $phone = ''): array
    {
        return $this->auth->updateProfile($name, $phone);
    }

    /**
     * Смена пароля: письмо с контрольным словом, далее password/reset.
     */
    public function passwordChangeAction(): array
    {
        return $this->auth->requestPasswordChange();
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