<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Internal\ErrorMessage;
use App\Auth\Registration\ConfirmRegistrationRequest;
use App\Auth\Registration\RegisterRequest;
use App\Auth\Registration\RegistrationService;
use Bitrix\Main\SystemException;
use CUser;

/**
 * Фасад авторизации: вход по e-mail и паролю, регистрация (подтверждение —
 * по опции ядра), обновление профиля, смена и восстановление пароля через
 * контрольное слово из письма (штатные механизмы CUser).
 */
final class AuthService
{
    private readonly UserLocator $users;
    private readonly RegistrationService $registration;

    public function __construct(?UserLocator $users = null)
    {
        $this->users = $users ?? new UserLocator();
        $this->registration = new RegistrationService($this->users);
    }

    /**
     * Вход по e-mail и паролю.
     *
     * @return array{user: array{id: int, login: string, email: string, name: string, phone: string}}
     */
    public function login(LoginRequest $data): array
    {
        global $USER;

        $user = $this->users->findOrFail($data->email, 'Неверный e-mail или пароль');

        $result = $USER->Login(
            (string) $user['LOGIN'],
            $data->password,
            $data->remember ? 'Y' : 'N',
        );

        if (is_array($result) && ($result['TYPE'] ?? '') === 'ERROR') {
            throw new SystemException(ErrorMessage::fromResult($result, 'Неверный e-mail или пароль'));
        }

        return ['user' => $this->users->getData((int) $USER->GetID())];
    }

    /**
     * @return array{registered: true, user?: array{id: int, login: string, email: string, name: string, phone: string}, confirmation?: array{email: string, resend_after: int}}
     */
    public function register(RegisterRequest $data): array
    {
        return $this->registration->register($data);
    }

    /**
     * Регистрация без подтверждения для заказа: данные из заказа, случайный пароль,
     * сразу авторизует; возвращает данные нового пользователя (включая пароль).
     *
     * @param array{email: string, name?: string, phone?: string} $data
     * @return array{id: int, login: string, email: string, name: string, phone: string, password: string}
     */
    public function registerByEmail(array $data): array
    {
        return $this->registration->registerByEmail($data);
    }

    /**
     * @return array{user: array{id: int, login: string, email: string, name: string, phone: string}}
     */
    public function confirmRegistration(ConfirmRegistrationRequest $data): array
    {
        return $this->registration->confirm($data);
    }

    /**
     * @return array{sent: true, resend_after: int}
     */
    public function resendConfirmation(string $email): array
    {
        return $this->registration->resend($email);
    }

    /**
     * Обновить имя и/или телефон текущего пользователя.
     *
     * @return array{user: array{id: int, login: string, email: string, name: string, phone: string}}
     */
    public function updateProfile(string $name = '', string $phone = ''): array
    {
        global $USER;

        $userId = (int) $USER->GetID();
        $fields = [];

        if (trim($name) !== '') {
            $fields['NAME'] = trim($name);
        }

        if (trim($phone) !== '') {
            $fields['PERSONAL_PHONE'] = trim($phone);
        }

        if ($fields !== [] && $USER->Update($userId, $fields) === false) {
            throw new SystemException(ErrorMessage::clean($USER->LAST_ERROR ?: 'Не удалось обновить профиль'));
        }

        return ['user' => $this->users->getData($userId)];
    }

    /**
     * Смена пароля: письмо с контрольным словом на e-mail текущего пользователя,
     * затем новый пароль устанавливается через resetPassword (как при восстановлении).
     *
     * @return array{sent: true}
     */
    public function requestPasswordChange(): array
    {
        global $USER;

        return $this->requestPasswordReset((string) $USER->GetEmail());
    }

    /**
     * Выход.
     *
     * @return array{logged_out: true}
     */
    public function logout(): array
    {
        global $USER;

        $USER->Logout();

        return ['logged_out' => true];
    }

    /**
     * Текущий авторизованный пользователь.
     *
     * @return array{user: array{id: int, login: string, email: string, name: string, phone: string}}
     */
    public function me(): array
    {
        global $USER;

        return ['user' => $this->users->getData((int) $USER->GetID())];
    }

    /**
     * @return array{captcha_sid: string, captcha_url: string}
     */
    public function getCaptcha(): array
    {
        global $APPLICATION;

        $sid = $APPLICATION->CaptchaGetCode();

        return [
            'captcha_sid' => $sid,
            'captcha_url' => '/bitrix/tools/captcha.php?captcha_sid=' . $sid,
        ];
    }

    /**
     * Отправить письмо с контрольным словом для восстановления пароля.
     *
     * Ядро отвечает одинаково и для существующего, и для неизвестного e-mail.
     *
     * @return array{sent: true}
     */
    public function requestPasswordReset(string $email, string $captchaWord = '', string|int $captchaSid = 0): array
    {
        $result = CUser::SendPassword('', trim($email), SITE_ID, $captchaWord, $captchaSid);

        if (is_array($result) && ($result['TYPE'] ?? 'OK') === 'ERROR') {
            throw new SystemException(ErrorMessage::fromResult($result, 'Не удалось отправить письмо'));
        }

        return ['sent' => true];
    }

    /**
     * Установить новый пароль по контрольному слову из письма.
     *
     * @return array{changed: true}
     */
    public function resetPassword(ResetPasswordRequest $data): array
    {
        global $USER;

        $user = $this->users->findActiveByEmail($data->email);

        if ($user === null) {
            throw new SystemException('Пользователь с таким e-mail не найден');
        }

        $result = $USER->ChangePassword(
            $user['LOGIN'],
            $data->checkword,
            $data->password,
            $data->passwordConfirm !== '' ? $data->passwordConfirm : $data->password,
            SITE_ID,
            $data->captchaWord,
            $data->captchaSid,
        );

        if (!is_array($result) || ($result['TYPE'] ?? 'OK') === 'ERROR') {
            throw new SystemException(ErrorMessage::fromResult($result, 'Не удалось изменить пароль'));
        }

        return ['changed' => true];
    }
}
