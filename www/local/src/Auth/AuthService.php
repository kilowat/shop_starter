<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Dto\ProfilePayload;
use Bitrix\Main\Authentication\Context;
use Bitrix\Main\Authentication\Method;
use Bitrix\Main\Config\Option;
use Bitrix\Main\UserTable;

class AuthService
{
    /**
     * Регистрирует обычного пользователя
     *
     * @return array{id: int, need_confirm: bool}
     */
    public function register(
        string $email,
        string $password,
        string $confirmPassword,
        ?ProfilePayload $payload = null,
        bool $authorize = false,
    ): array {
        global $USER;

        $email = trim($email);

        if (!check_email($email)) {
            throw new AuthException('Некорректный email');
        }

        if ($this->emailExists($email)) {
            throw new AuthException('Пользователь с таким email уже существует');
        }

        $needConfirm = Option::get('main', 'new_user_registration_email_confirmation') === 'Y';

        $code = $needConfirm ? str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT) : '';

        $fields = array_merge(
            [
                'NAME' => 'Пользователь ' . random_int(10000, 99999),
                'GROUP_ID' => $this->defaultGroups(),
                'LID' => $this->siteId(),
            ],
            $payload?->toFields() ?? [],
            [
                'LOGIN' => $email,
                'EMAIL' => $email,
                'PASSWORD' => $password,
                'CONFIRM_PASSWORD' => $confirmPassword,
                'ACTIVE' => $needConfirm ? 'N' : 'Y',
                'CONFIRM_CODE' => $code,
            ],
        );

        $user = new \CUser();
        $id = $user->Add($fields);

        if (!$id) {
            throw new AuthException($this->clean($user->LAST_ERROR));
        }

        if ($needConfirm) {
            \CEvent::Send('NEW_USER_CONFIRM', $this->siteId(), [
                'USER_ID' => $id,
                'EMAIL' => $email,
                'LOGIN' => $email,
                'CONFIRM_CODE' => $code,
            ]);
        } elseif ($authorize) {
            $USER->Authorize(
                (new Context())->setUserId((int) $id)->setMethod(Method::Registration),
                true,
            );
        }

        return ['id' => (int) $id, 'need_confirm' => $needConfirm];
    }

    public function login(string $login, string $password, bool $remember = true): void
    {
        global $USER;

        $this->check($USER->Login($login, $password, $remember ? 'Y' : 'N'));
    }

    public function logout(): void
    {
        global $USER;

        $USER->Logout();
    }

    public function confirmRegistration(string $email, string $code, bool $remember = true): void
    {
        global $USER;

        $user = UserTable::getRow([
            'select' => ['ID', 'ACTIVE', 'CONFIRM_CODE'],
            'filter' => ['=LOGIN' => trim($email)],
        ]);

        if (!$user) {
            throw new AuthException('Пользователь не найден');
        }

        if ($user['ACTIVE'] === 'Y') {
            throw new AuthException('Аккаунт уже подтверждён, выполните вход');
        }

        $expected = (string) $user['CONFIRM_CODE'];

        if ($expected === '' || !hash_equals($expected, trim($code))) {
            throw new AuthException('Неверный код подтверждения');
        }

        $userId = (int) $user['ID'];

        if (!(new \CUser())->Update($userId, ['ACTIVE' => 'Y', 'CONFIRM_CODE' => ''])) {
            throw new AuthException('Не удалось подтвердить аккаунт');
        }

        $USER->Authorize(
            (new Context())->setUserId($userId)->setMethod(Method::Registration),
            $remember,
        );
    }

    public function sendPasswordChangeCode(string $email): void
    {
        $email = trim($email);

        if (!check_email($email)) {
            throw new AuthException('Некорректный email');
        }

        \CUser::SendPassword($email, $email, $this->siteId());
    }

    public function resetPassword(string $email, string $checkword, string $password, string $passwordConfirm): void
    {
        global $USER;

        $this->check($USER->ChangePassword(
            trim($email),
            trim($checkword),
            $password,
            $passwordConfirm,
            $this->siteId(),
        ));
    }

    private function emailExists(string $email): bool
    {
        return (bool) UserTable::getRow([
            'select' => ['ID'],
            'filter' => [
                'LOGIC' => 'OR',
                ['=LOGIN' => $email],
                ['=EMAIL' => $email],
            ],
        ]);
    }

    /** Группы из «Настройки модулей → Главный модуль → Группа для новых пользователей» */
    private function defaultGroups(): array
    {
        $groups = (string) Option::get('main', 'new_user_registration_def_group', '');

        return array_values(array_filter(array_map('intval', explode(',', $groups))));
    }

    /** Бросает исключение, если Bitrix вернул ошибку */
    private function check(mixed $result): void
    {
        if (is_array($result) && ($result['TYPE'] ?? '') === 'ERROR') {
            throw new AuthException($this->clean((string) $result['MESSAGE']));
        }
    }

    private function clean(string $message): string
    {
        return trim(strip_tags(str_replace('<br>', "\n", $message)));
    }

    private function siteId(): string
    {
        return defined('SITE_ID') && SITE_ID !== '' ? SITE_ID : (string) \CSite::GetDefSite();
    }
}
