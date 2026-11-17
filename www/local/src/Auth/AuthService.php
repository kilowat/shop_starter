<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Dto\ConfirmRegistrationRequest;
use App\Auth\Dto\ForgotPasswordRequest;
use App\Auth\Dto\LoginRequest;
use App\Auth\Dto\RegisterRequest;
use App\Auth\Dto\ResendConfirmationRequest;
use App\Auth\Dto\ResetPasswordRequest;
use Bitrix\Main\Authentication\Context;
use Bitrix\Main\Authentication\Method;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use CEvent;
use CUser;

/**
 * Аутентификация: вход, выход, регистрация, текущий пользователь,
 * восстановление пароля по контрольному слову из письма.
 *
 * Входные данные приходят в DTO (валидация — атрибуты DTO через автовайр),
 * капча проверяется CaptchaActionFilter до экшена, поэтому в ядро она не передаётся.
 * Операции ничего не возвращают; данные пользователя — только me().
 */
final class AuthService
{
    public function __construct(
        private readonly UserService $users = new UserService(),
    ) {
    }

    public function login(LoginRequest $data): void
    {
        global $USER;

        $user = $this->users->findOrFail($data->email, 'Неверный e-mail или пароль');

        $result = $USER->Login($user['LOGIN'], $data->password, $data->remember ? 'Y' : 'N');

        if (is_array($result) && ($result['TYPE'] ?? '') === 'ERROR') {
            throw self::error($result, 'Неверный e-mail или пароль');
        }
    }

    /**
     * Регистрация: CUser::Add (уникальность e-mail и политику пароля проверяет ядро),
     * капча — фильтром экшена. Подтверждение по коду из письма — если включена опция ядра.
     */
    public function register(RegisterRequest $data): void
    {
        global $USER;

        $confirmation = $this->confirmationRequired();

        $userId = $this->users->create([
            'LOGIN' => $data->email,
            'EMAIL' => $data->email,
            'NAME' => $this->users->generateDisplayName(),
            'PASSWORD' => $data->password,
            'CONFIRM_PASSWORD' => $data->confirmPassword,
            'CHECKWORD' => Random::getString(32),
            'ACTIVE' => $confirmation ? 'N' : 'Y',
            'CONFIRM_CODE' => $confirmation ? Random::getString(8, true) : '',
            'LID' => $this->users->siteId(),
            'LANGUAGE_ID' => defined('LANGUAGE_ID') ? LANGUAGE_ID : 'ru',
            'GROUP_ID' => $this->users->defaultGroups(),
        ]);

        if ($confirmation) {
            $this->sendConfirmation(
                $this->users->findOrFail($data->email, 'Пользователь не найден', false)
            );

            return;
        }

        $USER->Authorize(
            (new Context())->setUserId($userId)->setMethod(Method::Registration),
        );
    }

    public function confirmRegistration(ConfirmRegistrationRequest $data): void
    {
        global $USER;

        $user = $this->users->findOrFail($data->email, 'Пользователь не найден', false);

        if ($user['ACTIVE'] === 'Y') {
            throw new SystemException('Аккаунт уже подтверждён, выполните вход');
        }

        $expected = (string) $user['CONFIRM_CODE'];

        if ($expected === '' || !hash_equals($expected, $data->code)) {
            throw new SystemException('Неверный код подтверждения');
        }

        $userId = (int) $user['ID'];
        $this->users->update($userId, ['ACTIVE' => 'Y', 'CONFIRM_CODE' => '']);

        $USER->Authorize(
            (new Context())->setUserId($userId)->setMethod(Method::Registration),
            $data->remember,
        );
    }

    public function resendConfirmation(ResendConfirmationRequest $data): void
    {
        $user = $this->users->find($data->email, false);

        if ($user !== null && $user['ACTIVE'] !== 'Y') {
            $this->sendConfirmation($user);
        }
    }

    public function logout(): void
    {
        global $USER;

        $USER->Logout();
    }

    /**
     * @return array{user: array{id: int, login: string, email: string, name: string, phone: string}}
     */
    public function me(): array
    {
        global $USER;

        return ['user' => $this->users->getData((int) $USER->GetID())];
    }

    /**
     * Письмо с контрольным словом. Ядро отвечает одинаково
     * для существующего и неизвестного e-mail.
     */
    public function requestPasswordReset(ForgotPasswordRequest $data): void
    {
        $result = CUser::SendPassword('', $data->email, $this->users->siteId());

        self::assertOk($result, 'Не удалось отправить письмо');
    }

    /**
     * Новый пароль по контрольному слову из письма.
     * Непереданный password_confirm означает «совпадает с password».
     */
    public function resetPassword(ResetPasswordRequest $data): void
    {
        global $USER;

        $user = $this->users->findOrFail($data->email, 'Пользователь с таким e-mail не найден');

        $result = $USER->ChangePassword(
            $user['LOGIN'],
            $data->checkword,
            $data->password,
            $data->passwordConfirm ?? $data->password,
            $this->users->siteId(),
        );

        self::assertOk($result, 'Не удалось изменить пароль');
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

    private static function assertOk(mixed $result, string $message): void
    {
        if (!is_array($result) || ($result['TYPE'] ?? 'OK') === 'ERROR') {
            throw self::error($result, $message);
        }
    }

    private static function error(mixed $result, string $message): SystemException
    {
        $text = is_array($result)
            ? str_replace('<br>', ' ', $result['MESSAGE'] ?? '')
            : '';

        return new SystemException($text !== '' ? $text : $message);
    }
}