<?php

namespace Controllers;

use App\Basket\BasketService;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use OpenApi\Attributes as OA;

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
                ActionFilter\HttpMethod::METHOD_POST
            ])
        ];

        return [
            'show' => [
                'prefilters' => [
                    new ActionFilter\HttpMethod([
                        ActionFilter\HttpMethod::METHOD_GET
                    ])
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

    #[OA\Get(
        path: '/api/basket',
        operationId: 'basketShow',
        summary: 'Получить корзину',
        tags: ['Basket'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/BasketResponse'
                )
            )
        ]
    )]
    public function showAction(): array
    {
        return $this->basket->getData();
    }

    #[OA\Post(
        path: '/api/basket/items',
        operationId: 'basketAdd',
        summary: 'Добавить товар',
        tags: ['Basket'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['productId'],
                properties: [
                    new OA\Property(
                        property: 'productId',
                        type: 'integer',
                        example: 101
                    ),
                    new OA\Property(
                        property: 'quantity',
                        type: 'number',
                        format: 'float',
                        default: 1
                    ),
                    new OA\Property(
                        property: 'props',
                        type: 'array',
                        items: new OA\Items(type: 'object')
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/BasketResponse'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ApiError'
                )
            ),
        ]
    )]
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

    #[OA\Post(
        path: '/api/basket/items/quantity',
        operationId: 'basketUpdateQuantity',
        summary: 'Изменить количество',
        tags: ['Basket'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['basketId', 'quantity'],
                properties: [
                    new OA\Property(
                        property: 'basketId',
                        type: 'integer',
                        example: 15
                    ),
                    new OA\Property(
                        property: 'quantity',
                        type: 'number',
                        format: 'float',
                        example: 3
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/BasketResponse'
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ApiError'
                )
            ),
        ]
    )]
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

    #[OA\Post(
        path: '/api/basket/items/delay',
        operationId: 'basketSetDelay',
        summary: 'Отложить / вернуть товар',
        tags: ['Basket'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['basketId'],
                properties: [
                    new OA\Property(
                        property: 'basketId',
                        type: 'integer',
                        example: 15
                    ),
                    new OA\Property(
                        property: 'delay',
                        type: 'boolean',
                        default: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/BasketResponse'
                )
            )
        ]
    )]
    public function setDelayAction(
        int $basketId,
        bool $delay = true
    ): array {
        $this->basket->setDelay($basketId, $delay);

        return $this->basket->getData();
    }

    #[OA\Post(
        path: '/api/basket/items/remove',
        operationId: 'basketRemove',
        summary: 'Удалить позицию',
        tags: ['Basket'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['basketId'],
                properties: [
                    new OA\Property(
                        property: 'basketId',
                        type: 'integer',
                        example: 15
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/BasketResponse'
                )
            )
        ]
    )]
    public function removeAction(int $basketId): array
    {
        $this->basket->removeById($basketId);

        return $this->basket->getData();
    }

    #[OA\Post(
        path: '/api/basket/clear',
        operationId: 'basketClear',
        summary: 'Очистить корзину',
        tags: ['Basket'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/BasketResponse'
                )
            )
        ]
    )]
    public function clearAction(): array
    {
        $this->basket->clear();

        return $this->basket->getData();
    }

    #[OA\Post(
        path: '/api/basket/coupon',
        operationId: 'basketApplyCoupon',
        summary: 'Применить купон (пустая строка очищает купоны)',
        tags: ['Basket'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['coupon'],
                properties: [
                    new OA\Property(
                        property: 'coupon',
                        type: 'string',
                        example: 'SALE2026'
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/BasketResponse'
                )
            )
        ]
    )]
    public function applyCouponAction(string $coupon = ''): array
    {
        $result = $this->basket->applyCoupon($coupon);

        return $this->basket->getData() + [
            'coupon_result' => $result
        ];
    }

    #[OA\Post(
        path: '/api/basket/coupon/remove',
        operationId: 'basketRemoveCoupon',
        summary: 'Удалить купон',
        tags: ['Basket'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['coupon'],
                properties: [
                    new OA\Property(
                        property: 'coupon',
                        type: 'string',
                        example: 'SALE2026'
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/BasketResponse'
                )
            )
        ]
    )]
    public function removeCouponAction(string $coupon): array
    {
        $this->basket->removeCoupon($coupon);

        return $this->basket->getData();
    }
}
