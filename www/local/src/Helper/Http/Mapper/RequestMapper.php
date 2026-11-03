<?php

namespace App\Helper\Http\Mapper;

use Bitrix\Main\HttpRequest;
use ReflectionClass;
use ReflectionProperty;
use ReflectionParameter;

trait RequestMapper
{
    /**
     * Создаёт DTO и заполняет его данными HTTP-запроса.
     */
    public static function fromRequest(HttpRequest $request): static
    {
        $data = self::getRequestData($request);

        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        // Если конструктор отсутствует, используем старый механизм.
        if ($constructor === null) {
            $dto = new static();

            foreach ($reflection->getProperties() as $property) {
                if (!$property->isPublic() || $property->isStatic()) {
                    continue;
                }

                $key = self::getPropertyKey($property);

                if (array_key_exists($key, $data)) {
                    $property->setValue($dto, $data[$key]);
                }
            }

            return $dto;
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                continue;
            }

            $key = self::getParameterKey($reflection, $parameter);

            if (array_key_exists($key, $data)) {
                $arguments[$parameter->getName()] = $data[$key];
            }
        }

        return new static(...$arguments);
    }

    /**
     * Собирает данные из HTTP-запроса.
     */
    private static function getRequestData(HttpRequest $request): array
    {
        $post = $request->getPostList()->toArray();
        $json = $request->getJsonList()->toArray();
        $files = $request->getFileList()->toArray();

        return array_replace(
            $post,
            $json,
            $files,
        );
    }

    /**
     * Получает внешний ключ параметра конструктора.
     */
    private static function getParameterKey(
        ReflectionClass $reflection,
        ReflectionParameter $parameter
    ): string {
        $name = $parameter->getName();

        if (!$reflection->hasProperty($name)) {
            return $name;
        }

        return self::getPropertyKey(
            $reflection->getProperty($name)
        );
    }

    /**
     * Получает внешний ключ свойства.
     */
    private static function getPropertyKey(
        ReflectionProperty $property
    ): string {
        $attributes = $property->getAttributes(Key::class);

        if ($attributes === []) {
            return $property->getName();
        }

        return $attributes[0]->newInstance()->key;
    }

    /**
     * Преобразует DTO в массив.
     */
    public function toArray(): array
    {
        $result = [];

        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties() as $property) {
            if (!$property->isPublic() || $property->isStatic()) {
                continue;
            }

            if (!$property->isInitialized($this)) {
                continue;
            }

            $value = $property->getValue($this);

            $attributes = $property->getAttributes(Key::class);

            if ($attributes !== []) {
                /** @var Key $attribute */
                $attribute = $attributes[0]->newInstance();

                if (!$attribute->includeIfNull && $value === null) {
                    continue;
                }
            }

            $key = self::getPropertyKey($property);

            $result[$key] = $value;
        }

        return $result;
    }
}
