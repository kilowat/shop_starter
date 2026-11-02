<?php

namespace App\Helper;

/**
 * Класс-хранилище для передачи данных между компонентами в рамках одного хита
 */
class DataStorage
{
    private static array $storage = [];

    /**
     * Сохранить значение
     */
    public static function set(string $name, mixed $value): void
    {
        self::$storage[$name] = $value;
    }

    /**
     * Получить значение
     */
    public static function get(string $name, mixed $default = null): mixed
    {
        return self::$storage[$name] ?? $default;
    }

    /**
     * Проверить существование
     */
    public static function has(string $name): bool
    {
        return isset(self::$storage[$name]);
    }
}
