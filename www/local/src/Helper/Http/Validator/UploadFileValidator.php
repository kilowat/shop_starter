<?php

declare(strict_types=1);

namespace App\Helper\Http\Validator;

use Bitrix\Main\Validation\ValidationError;
use Bitrix\Main\Validation\ValidationResult;
use Bitrix\Main\Validation\Validator\ValidatorInterface;

final class UploadFileValidator implements ValidatorInterface
{
    private const UPLOAD_ERRORS = [
        UPLOAD_ERR_INI_SIZE => 'Файл превышает допустимый размер сервера',
        UPLOAD_ERR_FORM_SIZE => 'Файл превышает допустимый размер формы',
        UPLOAD_ERR_PARTIAL => 'Файл загружен частично',
        UPLOAD_ERR_NO_TMP_DIR => 'На сервере нет временной папки',
        UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
        UPLOAD_ERR_EXTENSION => 'Загрузка остановлена расширением PHP',
    ];

    /**
     * @param int|null $maxSize      максимальный размер одного файла в байтах
     * @param string[] $extensions   разрешённые расширения без точки: ['jpg', 'png']
     * @param string[] $mimeTypes    разрешённые MIME: ['image/jpeg', 'image/png']
     * @param int|null $maxCount     максимум файлов (для множественной загрузки)
     * @param bool     $multiple     ожидается ли список файлов
     */
    public function __construct(
        private readonly ?int $maxSize = null,
        private readonly array $extensions = [],
        private readonly array $mimeTypes = [],
        private readonly ?int $maxCount = null,
        private readonly bool $multiple = false,
    ) {
    }

    public function validate(mixed $value): ValidationResult
    {
        $result = new ValidationResult();

        // Пустое значение проверяет #[NotEmpty], здесь пропускаем.
        if ($value === null) {
            return $result;
        }

        if (!is_array($value)) {
            return $result->addError(new ValidationError('Некорректный формат файла', 'file_invalid'));
        }

        $isList = array_is_list($value) && !array_key_exists('tmp_name', $value);

        if ($this->multiple !== $isList) {
            return $result->addError(new ValidationError(
                $this->multiple ? 'Ожидается список файлов' : 'Ожидается один файл',
                'file_invalid_structure'
            ));
        }

        $files = $isList ? $value : [$value];

        if ($this->maxCount !== null && count($files) > $this->maxCount) {
            $result->addError(new ValidationError(
                "Можно загрузить не более {$this->maxCount} файлов",
                'file_too_many'
            ));
        }

        foreach ($files as $file) {
            foreach ($this->validateOne($file) as $error) {
                $result->addError($error);
            }
        }

        return $result;
    }

    /**
     * @return ValidationError[]
     */
    private function validateOne(mixed $file): array
    {
        if (!is_array($file) || !isset($file['tmp_name'], $file['name'])) {
            return [new ValidationError('Некорректный формат файла', 'file_invalid')];
        }

        $name = (string) $file['name'];
        $error = (int) ($file['error'] ?? UPLOAD_ERR_OK);

        if ($error !== UPLOAD_ERR_OK) {
            return [
                new ValidationError(
                    "{$name}: " . (self::UPLOAD_ERRORS[$error] ?? 'Ошибка загрузки файла'),
                    'file_upload_error'
                )
            ];
        }

        $errors = [];
        $tmpName = (string) $file['tmp_name'];
        $size = (int) ($file['size'] ?? 0);

        if ($size <= 0) {
            $errors[] = new ValidationError("{$name}: файл пустой", 'file_empty');
        }

        if ($this->maxSize !== null && $size > $this->maxSize) {
            $mb = round($this->maxSize / 1024 / 1024, 1);
            $errors[] = new ValidationError("{$name}: размер больше {$mb} МБ", 'file_too_large');
        }

        if ($this->extensions) {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (!in_array($ext, array_map('strtolower', $this->extensions), true)) {
                $allowed = implode(', ', $this->extensions);
                $errors[] = new ValidationError(
                    "{$name}: недопустимое расширение (разрешены: {$allowed})",
                    'file_extension'
                );
            }
        }

        if ($this->mimeTypes && is_file($tmpName)) {
            // Проверяем реальный MIME по содержимому, а не $file['type'] от клиента.
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmpName) ?: '';

            if (!in_array($mime, $this->mimeTypes, true)) {
                $errors[] = new ValidationError(
                    "{$name}: недопустимый тип файла ({$mime})",
                    'file_mime'
                );
            }
        }

        return $errors;
    }
}