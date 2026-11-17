<?php

namespace App\Swagger\Schemas\Auth;

use OpenApi\Spec as OA;
use App\Swagger\Schemas\ApiError;

#[OA\Schema]
final class AuthResponse
{
    #[OA\Property]
    #[OA\Schema(type: 'string', example: 'success')]
    public string $status;

    #[OA\Property]
    #[OA\Schema(ref: AuthUser::class)]
    public AuthUser $data;

    #[OA\Property]
    #[OA\Schema(
        type: 'array',
        items: new OA\Schema(ref: ApiError::class),
    )]
    public array $errors;
}