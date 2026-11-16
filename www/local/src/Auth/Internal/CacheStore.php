<?php

declare(strict_types=1);

namespace App\Auth\Internal;

use Bitrix\Main\Data\Cache;

/**
 * Тонкая обёртка над кешем Битрикса для счётчиков и кулдаунов авторизации.
 */
final class CacheStore
{
    public function __construct(
        private readonly string $directory,
    ) {
    }

    public function get(string $key, int $ttl): ?array
    {
        $cache = Cache::createInstance();

        if (!$cache->initCache($ttl, $key, $this->directory)) {
            return null;
        }

        $vars = $cache->getVars();

        return is_array($vars) ? $vars : null;
    }

    public function put(string $key, array $value, int $ttl): void
    {
        $cache = Cache::createInstance();
        $cache->forceRewriting(true);

        if ($cache->startDataCache($ttl, $key, $this->directory)) {
            $cache->endDataCache($value);
        }
    }

    public function forget(string $key): void
    {
        Cache::createInstance()->clean($key, $this->directory);
    }
}
