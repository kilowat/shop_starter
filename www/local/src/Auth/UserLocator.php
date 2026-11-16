<?php

declare(strict_types=1);

namespace App\Auth;

use Bitrix\Main\SystemException;
use Bitrix\Main\UserTable;

/**
 * Поиск локальных пользователей по e-mail.
 */
final class UserLocator
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

    /**
     * @return array{ID: int|string, LOGIN: string, EMAIL: string|null, NAME: string|null, LAST_NAME: string|null, ACTIVE: string, EXTERNAL_AUTH_ID: string|null, CONFIRM_CODE: string|null}|null
     */
    public function findActiveByEmail(string $email): ?array
    {
        return $this->find($email);
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
