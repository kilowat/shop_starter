<?php

namespace App\Swagger\Paths;

use OpenApi\Spec as OA;

use App\Swagger\Schemas\ApiError;
use App\Swagger\Schemas\Basket\BasketResponse;

#[OA\Tag(name: 'Basket', description: 'Работа с корзиной')]
final class Basket
{
    // GET /api/basket

    #[OA\Operation\Get(
        path: '/api/basket',
        operationId: 'basketShow',
        summary: 'Получить корзину',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\Schema(ref: BasketResponse::class),
            ),
        ],
    )]
    public function show(): void
    {
    }


    // POST /api/basket/items

    #[OA\Operation\Post(
        path: '/api/basket/items',
        operationId: 'basketAdd',
        summary: 'Добавить товар',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        required: ['productId'],
                        properties: [
                            new OA\Property(
                                property: 'productId',
                                schema: new OA\Schema(type: 'integer', example: 101),
                            ),
                            new OA\Property(
                                property: 'quantity',
                                schema: new OA\Schema(type: 'number', format: 'float', default: 1),
                            ),
                            new OA\Property(
                                property: 'props',
                                schema: new OA\Schema(
                                    type: 'array',
                                    items: new OA\Schema(type: 'object'),
                                ),
                            ),
                        ],
                    ),
                ),
            ],
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\Schema(ref: BasketResponse::class),
            ),
            new OA\Response(
                response: 400,
                description: 'Ошибка запроса',
                content: new OA\Schema(ref: ApiError::class),
            ),
        ],
    )]
    public function add(): void
    {
    }


    // POST /api/basket/items/quantity

    #[OA\Operation\Post(
        path: '/api/basket/items/quantity',
        operationId: 'basketUpdateQuantity',
        summary: 'Изменить количество',
    )]
    public function updateQuantity(): void
    {
    }


    // POST /api/basket/items/delay

    #[OA\Operation\Post(
        path: '/api/basket/items/delay',
        operationId: 'basketSetDelay',
        summary: 'Отложить / вернуть товар',
    )]
    public function setDelay(): void
    {
    }


    // POST /api/basket/items/remove

    #[OA\Operation\Post(
        path: '/api/basket/items/remove',
        operationId: 'basketRemove',
        summary: 'Удалить позицию',
    )]
    public function remove(): void
    {
    }


    // POST /api/basket/clear

    #[OA\Operation\Post(
        path: '/api/basket/clear',
        operationId: 'basketClear',
        summary: 'Очистить корзину',
    )]
    public function clear(): void
    {
    }


    // POST /api/basket/coupon

    #[OA\Operation\Post(
        path: '/api/basket/coupon',
        operationId: 'basketApplyCoupon',
        summary: 'Применить купон',
    )]
    public function applyCoupon(): void
    {
    }


    // POST /api/basket/coupon/remove

    #[OA\Operation\Post(
        path: '/api/basket/coupon/remove',
        operationId: 'basketRemoveCoupon',
        summary: 'Удалить купон',
    )]
    public function removeCoupon(): void
    {
    }
}