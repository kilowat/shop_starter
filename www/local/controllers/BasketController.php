<?php

namespace Controllers;

use App\Basket\BasketRequest;
use App\Basket\BasketService;

use Bitrix\Main\Engine\Controller;


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
        return [];
    }

    public function getAutoWiredParameters(): array
    {
        return [
            new \Bitrix\Main\Validation\Engine\AutoWire\ValidationParameter(
                BasketRequest::class,
                fn() => BasketRequest::fromRequest($this->getRequest()),
            ),
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
    public function addAction(BasketRequest $request): array
    {
        $this->basket->add($request->productId, $request->quantity, $request->props);

        return $this->basket->getData();
    }

    /**
     * Изменить количество товара.
     */
    public function updateQuantityAction(BasketRequest $request): array
    {
        $this->basket->updateQuantity($request->basketId, $request->quantity);

        return $this->basket->getData();
    }

    /**
     * Отложить или вернуть товар.
     */
    public function setDelayAction(BasketRequest $request): array
    {
        $this->basket->setDelay($request->basketId, $request->delay);

        return $this->basket->getData();
    }

    /**
     * Удалить позицию из корзины.
     */
    public function removeAction(BasketRequest $request): array
    {
        $this->basket->removeById($request->basketId);

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
    public function applyCouponAction(BasketRequest $request): array
    {
        $result = $this->basket->applyCoupon($request->coupon);

        return $this->basket->getData() + [
            'coupon_result' => $result,
        ];
    }

    /**
     * Удалить купон.
     */
    public function removeCouponAction(BasketRequest $request): array
    {
        $this->basket->removeCoupon($request->coupon);

        return $this->basket->getData();
    }
}


