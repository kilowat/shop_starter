<?php

declare(strict_types=1);

namespace App\Auth;

use Bitrix\Main\Config\Option;
use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;
use CSite;
use CUser;

/**
 * Пользователи: поиск и манипуляции с записью в БД (создание, изменение,
 * мягкое удаление), а также общие хелперы идентичности и создания.
 */
final class UserService
{
    private const SELECT = ['ID', 'LOGIN', 'EMAIL', 'NAME', 'LAST_NAME', 'ACTIVE', 'EXTERNAL_AUTH_ID', 'CONFIRM_CODE'];

    /**
     * @return array{ID: int|string, LOGIN: string, EMAIL: string|null, NAME: string|null, LAST_NAME: string|null, ACTIVE: string, EXTERNAL_AUTH_ID: string|null, CONFIRM_CODE: string|null}|null
     */
    public function find(string $email, bool $onlyActive = true): ?array
    {
        $user = $this->findByEmail(trim($email));

        if ($user === null || ($onlyActive && $user['ACTIVE'] !== 'Y')) {
            return null;
        }

        return $user;
    }

    /**
     * @return array{ID: int|string, LOGIN: string, EMAIL: string|null, NAME: string|null, LAST_NAME: string|null, ACTIVE: string, EXTERNAL_AUTH_ID: string|null, CONFIRM_CODE: string|null}
     */
    public function findOrFail(string $email, string $message, bool $onlyActive = true): array
    {
        $user = $this->find($email, $onlyActive);

        if ($user === null) {
            throw new SystemException($message);
        }

        return $user;
    }

    public function emailExists(string $email): bool
    {
        return (bool) UserTable::getList([
            'select' => ['ID'],
            'filter' => ['=EMAIL' => trim($email)],
            'limit' => 1,
        ])->fetch();
    }

    /**
     * @return array{id: int, login: string, email: string, name: string, phone: string}
     */
    public function getData(int $userId): array
    {
        $user = UserTable::getList([
            'select' => ['ID', 'LOGIN', 'EMAIL', 'NAME', 'LAST_NAME', 'PERSONAL_PHONE'],
            'filter' => ['=ID' => $userId],
            'limit' => 1,
        ])->fetch();

        if (!$user) {
            throw new SystemException('Пользователь не найден');
        }

        return [
            'id' => (int) $user['ID'],
            'login' => (string) $user['LOGIN'],
            'email' => (string) $user['EMAIL'],
            'name' => trim($user['NAME'] . ' ' . $user['LAST_NAME']),
            'phone' => (string) ($user['PERSONAL_PHONE'] ?? ''),
        ];
    }

    /**
     * Создать пользователя (CUser::Add), вернуть ID.
     *
     * @param array<string, mixed> $fields
     */
    public function create(array $fields): int
    {
        global $USER;

        $userId = (int) $USER->Add($fields);

        if ($userId <= 0) {
            throw new SystemException(ErrorMessage::clean($USER->LAST_ERROR ?: 'Не удалось создать пользователя'));
        }

        return $userId;
    }

    /**
     * Изменить поля пользователя (CUser::Update).
     *
     * @param array<string, mixed> $fields
     */
    public function update(int $userId, array $fields): void
    {
        global $USER;

        if ($USER->Update($userId, $fields) === false) {
            throw new SystemException(ErrorMessage::clean($USER->LAST_ERROR ?: 'Не удалось обновить пользователя'));
        }
    }

    /**
     * Мягкое удаление: деактивация (ACTIVE='N'), запись физически не удаляется.
     */
    public function delete(int $userId): void
    {
        $this->update($userId, ['ACTIVE' => 'N']);
    }

    public function normalizeEmail(string $email): string
    {
        $email = trim($email);

        if (!check_email($email)) {
            throw new SystemException('Укажите корректный e-mail');
        }

        return $email;
    }

    public function assertEmailAvailable(string $email): void
    {
        if ($this->emailExists($email)) {
            throw new SystemException('Пользователь с таким e-mail уже зарегистрирован');
        }
    }

    public function generateDisplayName(): string
    {
        return 'Пользователь ' . random_int(10000, 99999);
    }

    /**
     * @return int[]
     */
    public function defaultGroups(): array
    {
        $groupId = (string) Option::get('main', 'new_user_registration_def_group', '');

        return $groupId !== '' ? array_map('intval', explode(',', $groupId)) : [];
    }

    public function siteId(): string
    {
        return defined('SITE_ID') && SITE_ID !== '' ? SITE_ID : (string) CSite::GetDefSite();
    }

    private function findByEmail(string $email): ?array
    {
        $users = UserTable::getList([
            'select' => self::SELECT,
            'filter' => ['=EMAIL' => $email],
            'order' => ['ID' => 'ASC'],
            'limit' => 2,
        ])->fetchAll();

        if (count($users) > 1) {
            throw new SystemException(
                'Этот e-mail привязан к нескольким аккаунтам, обратитесь к администратору'
            );
        }

        return $users[0] ?? null;
    }
}
