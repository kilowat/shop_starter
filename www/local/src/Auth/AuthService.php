<?php

declare(strict_types=1);

namespace App\Auth;

use Bitrix\Security\Mfa\UserTable;
use Exception;
use Bitrix\Main\Authentication\Method;
use Bitrix\Main\Authentication\Context;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Mail\Event;
class AuthService
{
    public function register(
        string $email,
        string $password,
        string $confirmPassword,
        string $name = '',
        string $lastName = '',
        string $phone = '',
        int $captchaSid = 0,
        string $captchaWord,
    ) {
        global $USER;

        $result = $USER->Register(
            USER_LOGIN: $email,
            USER_EMAIL: $email,
            USER_NAME: $name,
            USER_LAST_NAME: $lastName,
            USER_PASSWORD: $password,
            USER_CONFIRM_PASSWORD: $confirmPassword,
            USER_PHONE_NUMBER: $phone,
            captcha_sid: $captchaSid,
            captcha_word: $captchaWord,
        );

        $newUserId = $result['USER_ID'];

        if (is_array($result) && ($result['TYPE'] ?? '') === 'ERROR') {
            throw new Exception($result['MESSAGE']);
        }


        if ($this->confirmationRequired()) {
            $this->sendConfirmationToUser($newUserId);
        }

        $USER->Authorize(
            (new Context())->setUserId($newUserId)->setMethod(Method::Registration),
            true,
        );

        $USER->Authorize($result['USER_ID']);
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
            'select' => ['ID', 'ACTIVE'],
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
        $userId = $user['ID'];
        $newUser = new \CUser();

        $updateRes = $newUser->Update($userId, [
            'ACTIVE' => 'Y',
            'CONFIRM_CODE' => '',
        ]);

        if (!$updateRes) {
            throw new Exception('Ошибка подтерждения кода, пользователь не найден');
        }

        $USER->Authorize(
            (new Context())->setUserId($userId)->setMethod(Method::Registration),
            $remember,
        );
    }

    public function sendPasswordChangeCode(string $email, $captchaWord = '', $captchaSid = 0): void
    {
        $result = \CUser::SendPassword('', $email, $this->siteId(), $captchaWord, $captchaSid);

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
            'select' => ['ID', 'ACTIVE'],
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


    private function confirmationRequired(): bool
    {
        return Option::get('main', 'new_user_registration_email_confirmation', 'N') === 'Y'
            && Option::get('main', 'new_user_email_required', 'Y') !== 'N';
    }

    private function sendConfirmationToUser(int $userId): void
    {
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();

        $user = UserTable::getByPrimary($userId, [
            'select' => ['ID', 'LOGIN', 'EMAIL', 'NAME', 'LAST_NAME', 'CONFIRM_CODE']
        ])->fetch();

        Event::sendImmediate([
            'EVENT_NAME' => 'NEW_USER_CONFIRM',
            'LID' => $this->siteId(),
            'C_FIELDS' => [
                'USER_ID' => (int) $user['ID'],
                'LOGIN' => (string) $user['LOGIN'],
                'EMAIL' => (string) $user['EMAIL'],
                'NAME' => (string) $user['NAME'],
                'LAST_NAME' => (string) $user['LAST_NAME'],
                'CONFIRM_CODE' => (string) $user['CONFIRM_CODE'],
                'USER_IP' => (string) $request->getRemoteAddress(),
                'USER_HOST' => '',
            ],
        ]);
    }

    private function siteId(): string
    {
        return defined('SITE_ID') && SITE_ID !== '' ? SITE_ID : (string) CSite::GetDefSite();
    }

}