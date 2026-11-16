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
     * Создать активного пользователя со случайным паролем и сразу авторизовать
     * (например, заказ без регистрации). Возвращает данные, включая пароль,
     * для обработки на событиях и письма со сменой пароля.
     *
     * @param array{email: string, name?: string, phone?: string} $data
     * @return array{id: int, login: string, email: string, name: string, phone: string, password: string}
     */
    public function create(array $data): array
    {
        global $USER;

        $email = $this->users->normalizeEmail((string) ($data['email'] ?? ''));
        $this->users->assertEmailAvailable($email);

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

        return [
            'id' => $userId,
            'login' => $email,
            'email' => $email,
            'name' => $name,
            'phone' => $phone,
            'password' => $password,
        ];
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

        if ($fields !== []) {
            $this->users->update($userId, $fields);
        }

        return ['user' => $this->users->getData($userId)];
    }

    /**
     * Смена пароля: письмо с контрольным словом на e-mail текущего пользователя,
     * затем новый пароль устанавливается через Auth::resetPassword.
     *
     * @return array{sent: true}
     */
    public function requestPasswordChange(): array
    {
        global $USER;

        $result = CUser::SendPassword('', trim((string) $USER->GetEmail()), SITE_ID);

        if (is_array($result) && ($result['TYPE'] ?? 'OK') === 'ERROR') {
            throw new SystemException(ErrorMessage::fromResult($result, 'Не удалось отправить письмо'));
        }

        return ['sent' => true];
    }

    /**
     * Мягкое удаление профиля: деактивация текущего пользователя и выход.
     *
     * @return array{deleted: true}
     */
    public function deleteProfile(): array
    {
        global $USER;

        $this->users->delete((int) $USER->GetID());
        $USER->Logout();

        return ['deleted' => true];
    }
}
