<?php

declare(strict_types=1);

namespace App\Auth;

use Bitrix\Main\Authentication\Context;
use Bitrix\Main\Authentication\Method;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use CUser;

/**
 * Профиль пользователя: создание для заказа (create), изменение данных,
 * инициирование смены пароля и мягкое удаление (деактивация).
 */
final class ProfileService
{
    public function __construct(
        private readonly UserService $users = new UserService(),
    ) {
    }

    /**
     *
     * @param array{email: string, name?: string, phone?: string} $data
     */
    public function create(array $data): int
    {
        global $USER;

        $email = $data['email'];

        $name = trim((string) ($data['name'] ?? ''));
        $name = $name !== '' ? $name : $this->users->generateDisplayName();
        $phone = trim((string) ($data['phone'] ?? ''));
        $password = Random::getString(16);

        $fields = [
            'LOGIN' => $email,
            'EMAIL' => $email,
            'NAME' => $name,
            'PASSWORD' => $password,
            'CONFIRM_PASSWORD' => $password,
            'ACTIVE' => 'Y',
            'CHECKWORD' => Random::getString(32),
            'LID' => $this->users->siteId(),
            'LANGUAGE_ID' => defined('LANGUAGE_ID') ? LANGUAGE_ID : 'ru',
        ];

        $groups = $this->users->defaultGroups();

        if ($groups !== []) {
            $fields['GROUP_ID'] = $groups;
        }

        $userId = $this->users->create($fields);

        if ($phone !== '') {
            $this->users->update($userId, ['PERSONAL_PHONE' => $phone]);
        }

        $USER->Authorize(
            (new Context())->setUserId($userId)->setMethod(Method::Registration),
            true,
        );

        return $userId;
    }

    /**
     * Обновить имя и/или телефон текущего пользователя.
     *
     */
    public function updateProfile(string $name = '', string $phone = ''): void
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

        if ($fields !== []) {
            $this->users->update($userId, $fields);
        }

    }

    /**
     * Смена пароля: письмо с контрольным словом на e-mail текущего пользователя,
     * затем новый пароль устанавливается через Auth::resetPassword.
     *
     */
    public function requestPasswordChange(): void
    {
        global $USER;

        $result = CUser::SendPassword('', trim((string) $USER->GetEmail()), SITE_ID);

        if (is_array($result) && ($result['TYPE'] ?? 'OK') === 'ERROR') {
            throw new SystemException($result['MESSAGE']);
        }

        return;
    }

    /**
     * Мягкое удаление профиля: деактивация текущего пользователя и выход.
     *
     */
    public function deleteProfile(): void
    {
        global $USER;

        $this->users->deactive((int) $USER->GetID());
        $USER->Logout();

        return;
    }
}
