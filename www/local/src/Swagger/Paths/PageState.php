<?php

namespace App\Swagger\Paths;

use OpenApi\Spec as OA;

#[OA\Tag(name: 'PageState', description: 'Общее состояние шапки сайта')]
final class PageState
{
    #[OA\Operation\Get(
        path: '/api/page-state',
        operationId: 'pageState',
        summary: 'Получить мини-данные корзины и избранного',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Успешный ответ',
                content: new OA\Schema(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'v',
                            schema: new OA\Schema(type: 'integer', example: 1),
                        ),
                        new OA\Property(
                            property: 'basket',
                            schema: new OA\Schema(
                                type: 'object',
                                properties: [
                                    new OA\Property(
                                        property: 'count',
                                        schema: new OA\Schema(type: 'number', format: 'float', example: 2),
                                    ),
                                    new OA\Property(
                                        property: 'productIds',
                                        schema: new OA\Schema(
                                            type: 'array',
                                            items: new OA\Schema(type: 'integer'),
                                            example: [314, 41],
                                        ),
                                    ),
                                ],
                            ),
                        ),
                        new OA\Property(
                            property: 'favorites',
                            schema: new OA\Schema(
                                type: 'object',
                                properties: [
                                    new OA\Property(
                                        property: 'ids',
                                        schema: new OA\Schema(
                                            type: 'array',
                                            items: new OA\Schema(type: 'integer'),
                                            example: [314],
                                        ),
                                    ),
                                ],
                            ),
                        ),
                    ],
                ),
            ),
        ],
    )]
    public function show(): void
    {
    }
}