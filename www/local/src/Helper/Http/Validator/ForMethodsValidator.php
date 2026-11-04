<?php

declare(strict_types=1);

namespace App\Helper\Http\Validator;

use Bitrix\Main\Context;
use Bitrix\Main\Validation\Validator\ValidatorInterface;
use Bitrix\Main\Validation\ValidationResult;

/**
 * Декоратор: выполняет вложенный валидатор только для указанных HTTP-методов.
 * Любой штатный валидатор Битрикса можно сделать "зависимым от метода":
 *
 * new ForMethodsValidator(new NotEmptyValidator(), ['POST', 'PUT'])
 */
final class ForMethodsValidator implements ValidatorInterface
{
    /**
     * @param string[] $methods пусто = все методы
     */
    public function __construct(
        private readonly ValidatorInterface $inner,
        private readonly array $methods,
    ) {
    }

    public function validate(mixed $value): ValidationResult
    {
        if ($this->methods !== []) {
            $method = strtoupper(Context::getCurrent()->getRequest()->getRequestMethod());

            if (!in_array($method, $this->methods, true)) {
                return new ValidationResult();
            }
        }

        return $this->inner->validate($value);
    }
}
