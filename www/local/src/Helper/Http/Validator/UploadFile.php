<?php

declare(strict_types=1);

namespace App\Helper\Http\Validator;

use App\Validation\Validator\UploadedFileValidator;
use Attribute;
use Bitrix\Main\Localization\LocalizableMessageInterface;
use Bitrix\Main\Validation\Rule\AbstractPropertyValidationAttribute;
use Bitrix\Main\Validation\Rule\ValidateByGroupInterface;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class UploadFile extends AbstractPropertyValidationAttribute implements ValidateByGroupInterface
{
    public function __construct(
        private readonly ?int $maxSize = null,
        private readonly array $extensions = [],
        private readonly array $mimeTypes = [],
        private readonly ?int $maxCount = null,
        private readonly bool $multiple = false,
        protected string|LocalizableMessageInterface|null $errorMessage = null,
        protected array $groups = [],
    ) {
    }

    protected function getValidators(): array
    {
        return [
            new UploadedFileValidator(
                $this->maxSize,
                $this->extensions,
                $this->mimeTypes,
                $this->maxCount,
                $this->multiple,
            ),
        ];
    }

    public function getGroups(): array
    {
        return $this->groups;
    }
}