<?php

declare(strict_types=1);

namespace App\Auth;

/**
 * Сообщения ядра (CUser и т.п.) приходят с HTML — приводим их к plain-text для API.
 */
final class ErrorMessage
{
    public static function clean(string $message): string
    {
        return trim(strip_tags($message));
    }

    public static function fromResult(array|false $result, string $fallback): string
    {
        $message = is_array($result) ? trim((string) ($result['MESSAGE'] ?? '')) : '';

        return self::clean($message !== '' ? $message : $fallback);
    }
}
