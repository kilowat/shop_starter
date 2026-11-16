<?php

namespace App\Swagger\Schemas\Auth;

use OpenApi\Spec as OA;
use App\Swagger\Schemas\ApiError;

#[OA\Schema]
final class AuthResponse
{
    #[OA\Property(example: 'success')]
    public string $status;

    #[OA\Property]
    #[OA\Schema(ref: AuthData::class)]
    public AuthData $data;

    #[OA\Property]
    #[OA\Schema(
        type: 'array',
        items: new OA\Schema(ref: ApiError::class),
    )]
    public array $errors;
}
