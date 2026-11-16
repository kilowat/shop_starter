<?php

namespace App\Auth;

use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use CUser;

final class AuthService
{
    /**
     * Вход по e-mail и паролю (штатная авторизация Битрикса).
     *
     * @return array{user: array{id, login, email, name}}
     */
    public function login(LoginRequest $data): array
    {
        global $USER;

        $user = $this->findUserByEmail($data->email);

        $result = $USER->Login($user['LOGIN'], $data->password, $data->remember ? 'Y' : 'N');

        if (is_array($result) && ($result['TYPE'] ?? '') === 'ERROR') {
            throw new SystemException($this->cleanMessage($result['MESSAGE'] ?? 'Ошибка авторизации'));
        }

        return ['user' => $this->getUserData((int) $USER->GetID())];
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
     * @return array{user: array{id, login, email, name}}
     */
    public function me(): array
    {
        global $USER;

        return ['user' => $this->getUserData((int) $USER->GetID())];
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
     * Отправить письмо для восстановления пароля.
     *
     * Ядро отвечает одинаково и для существующего, и для неизвестного e-mail.
     *
     * @return array{sent: true}
     */
    public function requestPasswordReset(ForgotPasswordRequest $data): array
    {
        $result = CUser::SendPassword(
            '',
            trim($data->email),
            SITE_ID,
            $data->captchaWord,
            $data->captchaSid,
        );

        if (is_array($result) && ($result['TYPE'] ?? 'OK') === 'ERROR') {
            throw new SystemException($this->cleanMessage($result['MESSAGE'] ?? 'Не удалось отправить письмо'));
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

        $user = $this->findUserByEmail($data->email, 'Пользователь с таким e-mail не найден');

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
            throw new SystemException($this->cleanMessage($result['MESSAGE'] ?? 'Не удалось изменить пароль'));
        }

        return ['changed' => true];
    }

    /**
     * @return array{ID: string|int, LOGIN: string, ACTIVE: string, EXTERNAL_AUTH_ID: string|null}
     */
    private function findUserByEmail(string $email, string $notFoundMessage = 'Неверный e-mail или пароль'): array
    {
        $email = trim($email);

        $users = UserTable::getList([
            'select' => ['ID', 'LOGIN', 'ACTIVE', 'EXTERNAL_AUTH_ID'],
            'filter' => ['=EMAIL' => $email],
            'order' => ['ID' => 'ASC'],
        ])->fetchAll();

        $users = array_values(array_filter(
            $users,
            static fn(array $user): bool => trim((string) $user['EXTERNAL_AUTH_ID']) === '',
        ));

        if (count($users) > 1) {
            throw new SystemException('Этот e-mail привязан к нескольким аккаунтам, обратитесь к администратору');
        }

        if ($users === [] || $users[0]['ACTIVE'] !== 'Y') {
            throw new SystemException($notFoundMessage);
        }

        return $users[0];
    }

    /**
     * @return array{id: int, login: string, email: string, name: string}
     */
    private function getUserData(int $userId): array
    {
        $user = UserTable::getById($userId)->fetch();

        if (!$user) {
            throw new SystemException('Пользователь не найден');
        }

        return [
            'id' => (int) $user['ID'],
            'login' => (string) $user['LOGIN'],
            'email' => (string) $user['EMAIL'],
            'name' => trim($user['NAME'] . ' ' . $user['LAST_NAME']),
        ];
    }

    private function cleanMessage(string $message): string
    {
        return trim(strip_tags($message));
    }
}
