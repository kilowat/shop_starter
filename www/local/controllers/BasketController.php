<?php

namespace Controllers;

use App\Basket\BasketService;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;

final class BasketController extends Controller
{
    private BasketService $basket;

    protected function init(): void
    {
        parent::init();

        $this->basket = new BasketService();
    }

    public function configureActions(): array
    {
        $post = [
            new ActionFilter\HttpMethod([
                ActionFilter\HttpMethod::METHOD_POST,
            ]),
        ];

        return [
            'show' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_GET,
                    ]),
                ],
            ],

            'add' => ['prefilters' => $post],
            'updateQuantity' => ['prefilters' => $post],
            'setDelay' => ['prefilters' => $post],
            'remove' => ['prefilters' => $post],
            'clear' => ['prefilters' => $post],
            'applyCoupon' => ['prefilters' => $post],
            'removeCoupon' => ['prefilters' => $post],
        ];
    }

    /**
     * Получить корзину.
     */
    public function showAction(): array
    {
        return $this->basket->getData();
    }

    /**
     * Добавить товар.
     */
    public function addAction(
        int $productId,
        float $quantity = 1,
        array $props = []
    ): ?array {
        if ($productId <= 0 || $quantity <= 0) {
            $this->addError(
                new Error('Некорректные параметры', 'INVALID_PARAMS')
            );

            return null;
        }

        $this->basket->add($productId, $quantity, $props);

        return $this->basket->getData();
    }

    /**
     * Изменить количество товара.
     */
    public function updateQuantityAction(
        int $basketId,
        float $quantity
    ): ?array {
        if ($basketId <= 0 || $quantity <= 0) {
            $this->addError(
                new Error('Некорректные параметры', 'INVALID_PARAMS')
            );

            return null;
        }

        $this->basket->updateQuantity($basketId, $quantity);

        return $this->basket->getData();
    }

    /**
     * Отложить или вернуть товар.
     */
    public function setDelayAction(
        int $basketId,
        bool $delay = true
    ): array {
        $this->basket->setDelay($basketId, $delay);

        return $this->basket->getData();
    }

    /**
     * Удалить позицию из корзины.
     */
    public function removeAction(int $basketId): array
    {
        $this->basket->removeById($basketId);

        return $this->basket->getData();
    }

    /**
     * Очистить корзину.
     */
    public function clearAction(): array
    {
        $this->basket->clear();

        return $this->basket->getData();
    }

    /**
     * Применить купон.
     *
     * Пустая строка очищает купоны.
     */
    public function applyCouponAction(string $coupon = ''): array
    {
        $result = $this->basket->applyCoupon($coupon);

        return $this->basket->getData() + [
            'coupon_result' => $result,
        ];
    }

    /**
     * Удалить купон.
     */
    public function removeCouponAction(string $coupon): array
    {
        $this->basket->removeCoupon($coupon);

        return $this->basket->getData();
    }
}