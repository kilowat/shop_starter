<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Dto\ConfirmRegistrationRequest;
use App\Auth\Dto\LoginRequest;
use App\Auth\Dto\RegisterRequest;
use App\Auth\Dto\ResetPasswordRequest;
use Bitrix\Main\Authentication\Context;
use Bitrix\Main\Authentication\Method;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use CEvent;
use CUser;

/**
 * Аутентификация: вход, выход, регистрация (подтверждение — по опции ядра),
 * текущий пользователь, выдача кода восстановления и установка нового пароля
 * по контрольному слову из письма (штатные механизмы CUser).
 */
final class AuthService
{
    private readonly UserService $users;

    public function __construct(?UserService $users = null)
    {
        $this->users = $users ?? new UserService();
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
     * Регистрация по e-mail: пароль случайный, имя и телефон опциональны.
     *
     * @return array{registered: true, user?: array{id: int, login: string, email: string, name: string, phone: string}, confirmation?: array{email: string}}
     */
    public function register(RegisterRequest $data): array
    {
        global $USER;

        $email = $this->users->normalizeEmail($data->email);
        $this->users->assertEmailAvailable($email);

        $name = trim($data->name) !== '' ? trim($data->name) : $this->users->generateDisplayName();
        $password = Random::getString(16);

        $result = $USER->Register(
            $email,
            $name,
            '',
            $password,
            $password,
            $email,
            $this->users->siteId(),
            $data->captchaWord,
            (string) $data->captchaSid,
        );

        if (!is_array($result) || ($result['TYPE'] ?? 'OK') === 'ERROR' || empty($result['ID'])) {
            throw new SystemException(ErrorMessage::fromResult($result, 'Не удалось зарегистрироваться'));
        }

        $userId = (int) $result['ID'];

        if (trim($data->phone) !== '') {
            $this->users->update($userId, ['PERSONAL_PHONE' => trim($data->phone)]);
        }

        if (!$this->confirmationRequired()) {
            $USER->Authorize(
                (new Context())->setUserId($userId)->setMethod(Method::Registration),
                $data->remember,
            );

            return ['registered' => true, 'user' => $this->users->getData($userId)];
        }

        return [
            'registered' => true,
            'confirmation' => ['email' => $email],
        ];
    }

    /**
     * @return array{user: array{id: int, login: string, email: string, name: string, phone: string}}
     */
    public function confirmRegistration(ConfirmRegistrationRequest $data): array
    {
        global $USER;

        $email = $this->users->normalizeEmail($data->email);
        $user = $this->users->findOrFail($email, 'Пользователь не найден', false);

        if ($user['ACTIVE'] === 'Y') {
            throw new SystemException('Аккаунт уже подтверждён, выполните вход');
        }

        $expected = trim((string) $user['CONFIRM_CODE']);
        $code = trim($data->code);

        if ($expected === '' || $code === '' || !hash_equals($expected, $code)) {
            throw new SystemException('Неверный код подтверждения');
        }

        $userId = (int) $user['ID'];
        $this->users->update($userId, ['ACTIVE' => 'Y', 'CONFIRM_CODE' => '']);

        $USER->Authorize(
            (new Context())->setUserId($userId)->setMethod(Method::Registration),
            $data->remember,
        );

        return ['user' => $this->users->getData($userId)];
    }

    /**
     * @return array{sent: true}
     */
    public function resendConfirmation(string $email): array
    {
        $email = $this->users->normalizeEmail($email);
        $user = $this->users->find($email, false);

        if ($user !== null && $user['ACTIVE'] !== 'Y') {
            $this->sendConfirmation($user);
        }

        return ['sent' => true];
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

        $user = $this->users->find($data->email);

        if ($user === null) {
            throw new SystemException('Пользователь с таким e-mail не найден');
        }

        $result = $USER->ChangePassword(
            $user['LOGIN'],
            $data->checkword,
            $data->password,
            $data->passwordConfirm,
            SITE_ID,
            $data->captchaWord,
            $data->captchaSid,
        );

        if (!is_array($result) || ($result['TYPE'] ?? 'OK') === 'ERROR') {
            throw new SystemException(ErrorMessage::fromResult($result, 'Не удалось изменить пароль'));
        }

        return ['changed' => true];
    }

    private function confirmationRequired(): bool
    {
        return Option::get('main', 'new_user_registration_email_confirmation', 'N') === 'Y'
            && Option::get('main', 'new_user_email_required', 'Y') !== 'N';
    }

    /**
     * @param array{ID: int|string, LOGIN: string, EMAIL: string|null, NAME: string|null, LAST_NAME: string|null, CONFIRM_CODE: string|null, ...} $user
     */
    private function sendConfirmation(array $user): void
    {
        CEvent::SendImmediate('NEW_USER_CONFIRM', $this->users->siteId(), [
            'USER_ID' => (int) $user['ID'],
            'LOGIN' => (string) $user['LOGIN'],
            'EMAIL' => (string) $user['EMAIL'],
            'NAME' => (string) $user['NAME'],
            'LAST_NAME' => (string) $user['LAST_NAME'],
            'CONFIRM_CODE' => (string) $user['CONFIRM_CODE'],
            'USER_IP' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
            'USER_HOST' => '',
        ]);
    }
}
