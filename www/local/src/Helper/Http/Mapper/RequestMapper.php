<?php

namespace App\Helper\Http\Mapper;

use Bitrix\Main\HttpRequest;
use ReflectionClass;
use ReflectionProperty;

trait RequestMapper
{
    /**
     * Создаёт DTO и заполняет его данными HTTP-запроса.
     */
    public static function fromRequest(HttpRequest $request): static
    {
        $dto = new static();

        $data = self::getRequestData($request);

        $reflection = new ReflectionClass($dto);

        foreach ($reflection->getProperties() as $property) {
            if (!$property->isPublic() || $property->isStatic()) {
                continue;
            }

            $key = $dto->getPropertyKey($property);

            if (!array_key_exists($key, $data)) {
                continue;
            }

            $property->setValue($dto, $data[$key]);
        }

        return $dto;
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
     * Получает внешний ключ свойства.
     */
    private function getPropertyKey(ReflectionProperty $property): string
    {
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

            $key = $this->getPropertyKey($property);

            $result[$key] = $value;
        }

        return $result;
    }
}