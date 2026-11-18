<?php

namespace Controllers;

use App\Auth\AuthService;

use App\Auth\Dto\ConfirmRegistrationRequest;
use App\Auth\Dto\EmailRequest;
use App\Auth\Dto\LoginRequest;
use App\Auth\Dto\RegisterRequest;
use App\Auth\Dto\ResetPasswordRequest;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Validation\Engine\AutoWire\ValidationParameter;


/**
 * Аутентификация и восстановление пароля.
 */
final class AuthController extends ApiController
{
    private AuthService $authService;

    protected function init(): void
    {
        $this->authService = new AuthService();

        parent::init();
    }

    protected function getDefaultPreFilters(): array
    {
        return [];
    }

    public function configureActions(): array
    {
        return [
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
                EmailRequest::class,
                fn() => EmailRequest::fromRequest($this->getRequest()),
            ),
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
    public function loginAction(LoginRequest $request): void
    {
        $this->authService->login(
            login: $request->email,
            password: $request->password,
            remember: $request->remember,
        );
    }

    /**
     * Регистрация по e-mail и паролю.
     */
    public function registerAction(RegisterRequest $request): array
    {
        return $this->authService->register(
            email: $request->email,
            password: $request->password,
            confirmPassword: $request->confirmPassword,
            authorize: true,
        );
    }

    /**
     * Выход.
     */
    public function logoutAction(): void
    {
        $this->authService->logout();
    }

    /**
     * Текущий пользователь.
     */
    public function meAction(\Bitrix\Main\Engine\CurrentUser $user): ?array
    {
        return [
            'id' => (int) $user->getId(),
            'name' => $user->getFirstName(),
            'full_name' => $user->getFullName(),
            'email' => $user->getEmail(),
        ];
    }


    /**
     * Подтверждение регистрации кодом из письма.
     */
    public function confirmRegistrationAction(ConfirmRegistrationRequest $request): void
    {
        $this->authService->confirmRegistration(
            email: $request->email,
            code: $request->code,
            remember: $request->remember,
        );
    }

    public function forgotPasswordAction(EmailRequest $request)
    {
        $this->authService->sendPasswordChangeCode($request->email);
    }

    public function resetPasswordAction(ResetPasswordRequest $request)
    {
        $this->authService->resetPassword(
            email: $request->email,
            checkword: $request->checkword,
            password: $request->password,
            passwordConfirm: $request->passwordConfirm,
        );
    }
}
