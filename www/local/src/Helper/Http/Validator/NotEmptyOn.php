<?php

declare(strict_types=1);

namespace App\Helper\Http\Validator;

use App\Helper\Http\Mapper\RequestMethod;
use Attribute;
use Bitrix\Main\Localization\LocalizableMessageInterface;
use Bitrix\Main\Validation\Rule\NotEmpty;
use Bitrix\Main\Validation\Validator\NotEmptyValidator;

/**
 * Штатный NotEmpty, но только для указанных HTTP-методов.
 * Метод берётся из текущего запроса (Context), группы не используются,
 * поэтому работает со штатным ValidationParameter.
 *
 * Проверяет ЗНАЧЕНИЕ: '' и null не проходят, 0 проходит только с allowZero: true.
 * Если нужно "передать, но можно пустое" - RequiredOn.
 *
 * #[NotEmptyOn([RequestMethod::Post, RequestMethod::Put])]
 * #[NotEmptyOn(['POST'], allowZero: true)]
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class NotEmptyOn extends NotEmpty
{
    /** @var string[] */
    private readonly array $methods;
    private readonly bool $zeroAllowed;
    private readonly bool $spacesAllowed;

    /**
     * @param array<RequestMethod|string> $methods
     */
    public function __construct(
        array $methods,
        bool $allowZero = false,
        bool $allowSpaces = false,
        string|LocalizableMessageInterface|null $errorMessage = null,
    ) {
        parent::__construct($allowZero, $allowSpaces, $errorMessage);

        $this->methods = RequestMethod::normalize($methods);
        $this->zeroAllowed = $allowZero;
        $this->spacesAllowed = $allowSpaces;
    }

    protected function getValidators(): array
    {
        return [
            new ForMethodsValidator(
                new NotEmptyValidator($this->zeroAllowed, $this->spacesAllowed),
                $this->methods
            ),
        ];
    }
}
