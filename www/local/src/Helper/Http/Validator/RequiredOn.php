<?php

declare(strict_types=1);

namespace App\Helper\Http\Validator;

use App\Helper\Http\Mapper\RequestMethod;
use Attribute;

/**
 * Поле должно ПРИСУТСТВОВАТЬ в запросе, значение при этом может быть пустым
 * ('' , 0, false; null - только если allowNull: true).
 *
 * #[RequiredOn([RequestMethod::Post, RequestMethod::Put])]
 * #[RequiredOn]                          // для любого метода
 * #[RequiredOn(allowNull: false)]        // JSON {"x": null} считается непереданным
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
final class RequiredOn
{
    /** @var string[] */
    public readonly array $methods;

    /**
     * @param array<RequestMethod|string> $methods пусто = все методы
     */
    public function __construct(
        array $methods = [],
        public readonly bool $allowNull = true,
        public readonly ?string $message = null,
    ) {
        $this->methods = RequestMethod::normalize($methods);
    }

    public function appliesTo(?string $method): bool
    {
        return $this->methods === [] || in_array($method, $this->methods, true);
    }
}
