<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Dto\UpdateProfileRequest;
use Bitrix\Main\Authentication\Context;
use Bitrix\Main\Authentication\Method;
use Bitrix\Main\Security\Random;
use Bitrix\Main\SystemException;
use CUser;

/**
 * Профиль пользователя: создание для заказа, изменение данных,
 * инициирование смены пароля и мягкое удаление (деактивация).
 */
final class ProfileService
{
    public function __construct(
        private readonly UserService $users = new UserService(),
    ) {
    }

    /**
     * Создать пользователя для заказа (без регистрации) и сразу авторизовать.
     *
     * @param array{email: string, name?: string, phone?: string} $data
     */
    public function create(array $data): int
    {
        global $USER;

        $email = $data['email'];
        $name = $data['name'] ?? '';
        $phone = $data['phone'] ?? '';
        $password = Random::getString(16);

        $fields = [
            'LOGIN' => $email,
            'EMAIL' => $email,
            'NAME' => $name !== '' ? $name : $this->users->generateDisplayName(),
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
     * Поля приходят из DTO: toFields() отдаёт только заполненные.
     */
    public function updateProfile(UpdateProfileRequest $data): void
    {
        global $USER;

        $userId = (int) $USER->GetID();
        $fields = $data->toFields();

        if ($fields !== []) {
            $this->users->update($userId, $fields);
        }
    }

    /**
     * Письмо с контрольным словом на e-mail текущего пользователя;
     * новый пароль устанавливается через AuthService::resetPassword.
     */
    public function requestPasswordChange(): void
    {
        global $USER;

        $result = CUser::SendPassword('', (string) $USER->GetEmail(), $this->users->siteId());

        if (is_array($result) && ($result['TYPE'] ?? 'OK') === 'ERROR') {
            throw new SystemException(str_replace('<br>', ' ', $result['MESSAGE'] ?? ''));
        }
    }

    /**
     * Мягкое удаление: деактивация текущего пользователя и выход.
     */
    public function deleteProfile(): void
    {
        global $USER;

        $this->users->deactive((int) $USER->GetID());
        $USER->Logout();
    }
}