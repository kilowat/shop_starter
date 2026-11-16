<?php

declare(strict_types=1);

namespace App\Auth;

use Bitrix\Main\Data\Cache;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\UserTable;

final class LoginAttempts
{
    private const CACHE_TTL = 3600;
    private const CACHE_DIR = '/auth/login_attempts/';

    public function get(string $email): int
    {
        $row = UserTable::getList([
            'select' => ['LOGIN_ATTEMPTS'],
            'filter' => ['=EMAIL' => trim($email)],
            'runtime' => [
                new IntegerField('LOGIN_ATTEMPTS'),
            ],
            'order' => ['ID' => 'ASC'],
            'limit' => 1,
        ])->fetch();

        return max((int) ($row['LOGIN_ATTEMPTS'] ?? 0), $this->getCounter($email));
    }

    public function hit(string $email): void
    {
        $count = $this->getCounter($email) + 1;

        $cache = Cache::createInstance();
        $cache->forceRewriting(true);

        if ($cache->startDataCache(self::CACHE_TTL, $this->getCacheKey($email), self::CACHE_DIR)) {
            $cache->endDataCache(['count' => $count]);
        }
    }

    public function clear(string $email): void
    {
        Cache::createInstance()->clean($this->getCacheKey($email), self::CACHE_DIR);
    }

    private function getCounter(string $email): int
    {
        $cache = Cache::createInstance();

        if (!$cache->initCache(self::CACHE_TTL, $this->getCacheKey($email), self::CACHE_DIR)) {
            return 0;
        }

        $vars = $cache->getVars();

        return is_array($vars) ? (int) ($vars['count'] ?? 0) : 0;
    }

    private function getCacheKey(string $email): string
    {
        return 'attempts_' . md5(mb_strtolower(trim($email)));
    }
}