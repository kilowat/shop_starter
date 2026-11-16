<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Internal\CacheStore;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\UserTable;

/**
 * Счётчик неудачных попыток входа по e-mail: кеш для всех + LOGIN_ATTEMPTS из БД
 * для существующих пользователей (берётся максимум из двух источников).
 */
final class LoginAttempts
{
    private const CACHE_TTL = 3600;
    private const CACHE_DIR = '/auth/login_attempts/';

    private readonly UserLocator $users;
    private readonly CacheStore $cache;

    public function __construct(?UserLocator $users = null)
    {
        $this->users = $users ?? new UserLocator();
        $this->cache = new CacheStore(self::CACHE_DIR);
    }

    public function get(string $email): int
    {
        $counter = $this->cachedCount($email);
        $userId = $this->findUserId($email);

        if ($userId === null) {
            return $counter;
        }

        $row = UserTable::getList([
            'select' => ['ID', 'LOGIN_ATTEMPTS'],
            'filter' => ['=ID' => $userId],
            'runtime' => [
                new IntegerField('LOGIN_ATTEMPTS'),
            ],
            'limit' => 1,
        ])->fetch();

        return max((int) ($row['LOGIN_ATTEMPTS'] ?? 0), $counter);
    }

    public function hit(string $email): void
    {
        $this->cache->put(
            $this->cacheKey($email),
            ['count' => $this->cachedCount($email) + 1],
            self::CACHE_TTL,
        );
    }

    public function clear(string $email): void
    {
        $this->cache->forget($this->cacheKey($email));
    }

    private function cachedCount(string $email): int
    {
        $vars = $this->cache->get($this->cacheKey($email), self::CACHE_TTL);

        return (int) ($vars['count'] ?? 0);
    }

    private function findUserId(string $email): ?int
    {
        $user = $this->users->find($email, false);

        return $user !== null ? (int) $user['ID'] : null;
    }

    private function cacheKey(string $email): string
    {
        return 'attempts_' . md5(mb_strtolower(trim($email)));
    }
}
