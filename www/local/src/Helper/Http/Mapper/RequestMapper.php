<?php

declare(strict_types=1);

namespace App\Helper\Http\Mapper;

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Error;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\Validation\ValidationResult;
use Bitrix\Main\Validation\ValidationService;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionType;

trait RequestMapper
{
    /**
     * Создаёт DTO и заполняет его данными HTTP-запроса.
     *
     * Обязательное поле — не nullable и без дефолта. Если оно не передано
     * (или пришёл null), бросает BinderArgumentException с ошибками по ключам
     * полей (штатный автовайр превратит её в ошибки ответа; при ручном вызове лови сам).
     *
     * @throws BinderArgumentException
     */
    public static function fromRequest(HttpRequest $request): static
    {
        return self::build(self::getRequestData($request));
    }

    /**
     * Ручная валидация (вне автовайра) - тот же штатный ValidationService.
     * Правила #[NotEmptyOn] сами смотрят на метод запроса.
     */
    public function validateRequest(): ValidationResult
    {
        /** @var ValidationService $service */
        $service = ServiceLocator::getInstance()->get('main.validation.service');

        return $service->validate($this);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function build(array $data): static
    {
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
        $errors = [];

        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isVariadic()) {
                continue;
            }

            $key = self::getParameterKey($reflection, $parameter);

            if (array_key_exists($key, $data) && $data[$key] !== null) {
                $arguments[$parameter->getName()] = self::castValue(
                    $data[$key],
                    $parameter->getType()
                );
            } elseif (!$parameter->isDefaultValueAvailable()) {
                if ($parameter->allowsNull()) {
                    $arguments[$parameter->getName()] = null;
                } else {
                    $errors[] = new Error("Поле «{$key}» обязательно для передачи", $key);
                }
            }
        }

        if ($errors !== []) {
            throw new BinderArgumentException(
                'Required fields are missing',
                addedErrorsFromClosure: $errors
            );
        }

        return new static(...$arguments);
    }

    /**
     * Приоритет (от низкого к высокому): query < body (json или post) < files.
     * Query и body проходят санитайзинг по #[Sanitize], файлы - нет.
     */
    private static function getRequestData(HttpRequest $request): array
    {
        $query = self::sanitize($request->getQueryList()->toArray());

        $body = self::sanitize(
            $request->isJson()
            ? $request->getJsonList()->toArray()
            : $request->getPostList()->toArray()
        );

        $files = self::normalizeFiles($request->getFileList()->toArray());

        return array_replace($query, $body, $files);
    }

    /**
     * Применяет фильтры из #[Sanitize] к полям, которые есть в DTO.
     * Без атрибута действует Trim. Filter::Raw отключает обработку.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function sanitize(array $data): array
    {
        $reflection = new ReflectionClass(static::class);

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $key = self::getPropertyKey($property);

            if (!array_key_exists($key, $data)) {
                continue;
            }

            $attributes = $property->getAttributes(Sanitize::class);

            $filters = $attributes === []
                ? [Filter::Trim]
                : $attributes[0]->newInstance()->filters;

            if (in_array(Filter::Raw, $filters, true)) {
                continue;
            }

            $data[$key] = self::applyFilters($data[$key], $filters);
        }

        return $data;
    }

    /**
     * Строки обрабатываются фильтрами, массивы - рекурсивно, остальное не трогаем.
     *
     * @param Filter[] $filters
     */
    private static function applyFilters(mixed $value, array $filters): mixed
    {
        if (is_string($value)) {
            foreach ($filters as $filter) {
                $value = $filter->apply($value);
            }

            return $value;
        }

        if (is_array($value)) {
            return array_map(static fn($item) => self::applyFilters($item, $filters), $value);
        }

        return $value;
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