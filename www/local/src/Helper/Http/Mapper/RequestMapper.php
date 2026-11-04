<?php

declare(strict_types=1);

namespace App\Helper\Http\Mapper;

use Bitrix\Main\HttpRequest;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;

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

        if ($constructor === null) {
            $dto = new static();

            foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                if ($property->isStatic() || $property->isReadOnly()) {
                    continue;
                }

                $key = self::getPropertyKey($property);

                if (array_key_exists($key, $data)) {
                    $property->setValue(
                        $dto,
                        self::castValue($data[$key], $property->getType())
                    );
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
                $arguments[$parameter->getName()] = self::castValue(
                    $data[$key],
                    $parameter->getType()
                );
            } elseif (!$parameter->isDefaultValueAvailable() && $parameter->allowsNull()) {
                $arguments[$parameter->getName()] = null;
            }
        }

        return new static(...$arguments);
    }

    /**
     * Приоритет (от низкого к высокому): query < body (json или post) < files.
     */
    private static function getRequestData(HttpRequest $request): array
    {
        $query = $request->getQueryList()->toArray();

        $body = $request->isJson()
            ? $request->getJsonList()->toArray()
            : $request->getPostList()->toArray();

        $files = self::normalizeFiles($request->getFileList()->toArray());

        return array_replace($query, $body, $files);
    }

    /**
     * Убирает "пустые" загрузки (файл не выбран), чтобы поле считалось не переданным.
     */
    private static function normalizeFiles(array $files): array
    {
        $result = [];

        foreach ($files as $key => $file) {
            if (!is_array($file)) {
                continue;
            }

            // Один файл.
            if (array_key_exists('tmp_name', $file)) {
                if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_NO_FILE) {
                    $result[$key] = $file;
                }
                continue;
            }

            // Список файлов.
            $list = array_values(array_filter(
                $file,
                static fn($item) => is_array($item)
                    && (int) ($item['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_NO_FILE
            ));

            if ($list !== []) {
                $result[$key] = $list;
            }
        }

        return $result;
    }

    /**
     * Мягкое приведение: ошибок не бросает. Невалидное значение превращается в null
     * (если тип nullable), дальше его ловит валидация Битрикса.
     */
    private static function castValue(mixed $value, ?ReflectionType $type): mixed
    {
        if (!$type instanceof ReflectionNamedType || !$type->isBuiltin()) {
            return $value;
        }

        if ($value === '' && $type->allowsNull()) {
            return null;
        }

        if (!is_scalar($value)) {
            return $value;
        }

        $cast = match ($type->getName()) {
            'int' => filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE),
            'float' => filter_var($value, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE),
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'string' => (string) $value,
            default => $value,
        };

        return $cast ?? ($type->allowsNull() ? null : $value);
    }

    private static function getParameterKey(
        ReflectionClass $reflection,
        ReflectionParameter $parameter
    ): string {
        $name = $parameter->getName();

        return $reflection->hasProperty($name)
            ? self::getPropertyKey($reflection->getProperty($name))
            : $name;
    }

    private static function getPropertyKey(ReflectionProperty $property): string
    {
        $attributes = $property->getAttributes(Key::class);

        return $attributes === []
            ? $property->getName()
            : $attributes[0]->newInstance()->key;
    }

    /**
     * Преобразует DTO в массив.
     *
     * Поле с #[Key(..., includeIfNull: false)] пропускается, если оно null.
     * $skipNulls = true пропускает null у всех полей.
     */
    public function toArray(bool $skipNulls = false): array
    {
        $result = [];
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic() || !$property->isInitialized($this)) {
                continue;
            }

            $value = $property->getValue($this);

            if ($value === null) {
                if ($skipNulls) {
                    continue;
                }

                $attributes = $property->getAttributes(Key::class);

                if ($attributes !== [] && !$attributes[0]->newInstance()->includeIfNull) {
                    continue;
                }
            }

            $result[self::getPropertyKey($property)] = $value;
        }

        return $result;
    }

    /**
     * Поля для Bitrix: только свойства с #[Field] и не null.
     */
    public function toFields(): array
    {
        $result = [];

        foreach ((new ReflectionClass($this))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes = $property->getAttributes(Field::class);

            if ($attributes === [] || !$property->isInitialized($this)) {
                continue;
            }

            $value = $property->getValue($this);

            if ($value === null) {
                continue;
            }

            /** @var Field $field */
            $field = $attributes[0]->newInstance();

            if ($field->cast !== null) {
                $value = $field->cast->apply($value);
            }

            // 'PROPERTY_VALUES.TAGS' -> ['PROPERTY_VALUES' => ['TAGS' => ...]]
            $target = &$result;
            foreach (explode('.', $field->name) as $part) {
                $target = &$target[$part];
            }
            $target = $value;
            unset($target);
        }

        return $result;
    }
}