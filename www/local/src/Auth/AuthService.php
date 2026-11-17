<?php

declare(strict_types=1);

namespace App\Auth;

use Bitrix\Main\UserTable;
use Exception;
use Bitrix\Main\Authentication\Method;
use Bitrix\Main\Authentication\Context;

class AuthService
{
    public function register(
        string $email,
        string $password,
        string $confirmPassword,
        string $captchaWord = '',
        string $captchaSid = '',
    ): void {
        global $USER;

        $result = $USER->Register(
            USER_LOGIN: $email,
            USER_EMAIL: $email,
            USER_NAME: 'Пользователь ' . random_int(10000, 99999),
            USER_LAST_NAME: '',
            USER_PASSWORD: $password,
            USER_CONFIRM_PASSWORD: $confirmPassword,
            captcha_sid: $captchaSid,
            captcha_word: $captchaWord,
        );

        if (is_array($result) && ($result['TYPE'] ?? '') === 'ERROR') {
            throw new Exception($result['MESSAGE']);
        }
    }
    public function login(string $login, string $password, bool $remember = true): void
    {
        global $USER;

        $result = $USER->Login($login, $password, $remember ? 'Y' : 'N');

        if (is_array($result) && ($result['TYPE'] ?? '') === 'ERROR') {
            throw new Exception($result['MESSAGE']);
        }
    }


    public function logout(): void
    {
        global $USER;

        $USER->Logout();
    }


    public function confirmRegistration(string $email, string $code, bool $remember = true): void
    {
        global $USER;

        $user = UserTable::getList([
            'select' => ['ID', 'ACTIVE', 'CONFIRM_CODE'],
            'filter' => ['=EMAIL' => $email,],
            'limit' => 1,
        ])->fetch();

        if (!$user) {
            throw new Exception('Пользователь не найден');
        }

        if ($user['ACTIVE'] === 'Y') {
            throw new Exception('Аккаунт уже подтверждён, выполните вход');
        }

        $expected = (string) $user['CONFIRM_CODE'];

        if ($expected === '' || !hash_equals($expected, $code)) {
            throw new Exception('Неверный код подтверждения');
        }
        $userId = (int) $user['ID'];
        $newUser = new \CUser();

        $updateRes = $newUser->Update($userId, [
            'ACTIVE' => 'Y',
            'CONFIRM_CODE' => '',
        ]);

        if (!$updateRes) {
            throw new Exception('Ошибка подтверждения кода, пользователь не найден');
        }

        $USER->Authorize(
            (new Context())->setUserId($userId)->setMethod(Method::Registration),
            $remember,
        );
    }

    public function sendPasswordChangeCode(string $email): void
    {
        $result = \CUser::SendPassword('', $email, $this->siteId());

        if (is_array($result) && ($result['TYPE'] ?? '') === 'ERROR') {
            throw new Exception($result['MESSAGE']);
        }
    }


    public function resetPassword(
        string $email,
        string $checkword,
        string $password,
        ?string $passwordConfirm,
    ): void {
        global $USER;

        $user = UserTable::getList([
            'select' => ['ID', 'LOGIN', 'ACTIVE'],
            'filter' => ['=EMAIL' => $email, 'ACTIVE' => 'Y'],
            'limit' => 1,
        ])->fetch();

        if (!$user) {
            throw new Exception('Пользователь не найден');
        }

        $result = $USER->ChangePassword(
            $user['LOGIN'],
            $checkword,
            $password,
            $passwordConfirm,
            $this->siteId(),
        );

        if (is_array($result) && ($result['TYPE'] ?? '') === 'ERROR') {
            throw new Exception($result['MESSAGE']);
        }
    }


    private function siteId(): string
    {
        return defined('SITE_ID') && SITE_ID !== '' ? SITE_ID : (string) \CSite::GetDefSite();
    }

}