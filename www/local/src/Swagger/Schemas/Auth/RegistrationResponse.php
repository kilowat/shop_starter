<?php

namespace App\Swagger\Schemas\Auth;

use OpenApi\Spec as OA;
use App\Swagger\Schemas\ApiError;

#[OA\Schema]
final class RegistrationResponse
{
    #[OA\Property(example: 'success')]
    public string $status;

    #[OA\Property]
    #[OA\Schema(ref: RegistrationData::class)]
    public RegistrationData $data;

    #[OA\Property]
    #[OA\Schema(
        type: 'array',
        items: new OA\Schema(ref: ApiError::class),
    )]
    public array $errors;
}

#[OA\Schema]
final class RegistrationData
{
    #[OA\Property(example: true)]
    public bool $registered;

    #[OA\Property(description: 'Пользователь сразу авторизован, если подтверждение по e-mail не требуется')]
    #[OA\Schema(ref: AuthUser::class)]
    public ?AuthUser $user;

    #[OA\Property(description: 'Присутствует, если включена опция ядра new_user_registration_email_confirmation')]
    #[OA\Schema(ref: RegistrationConfirmation::class)]
    public ?RegistrationConfirmation $confirmation;
}

#[OA\Schema]
final class RegistrationConfirmation
{
    #[OA\Property(example: 'ivanov@example.com')]
    public string $email;

    #[OA\Property(property: 'resend_after', example: 60, description: 'Через сколько секунд можно запросить код повторно')]
    public int $resendAfter;
}