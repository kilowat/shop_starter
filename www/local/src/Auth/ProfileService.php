<?php

declare(strict_types=1);

namespace App\Auth;

use Bitrix\Main\AccessDeniedException;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\SystemException;

class ProfileService
{
    public function update()
    {
        global $USER;
        // to do
        $USER->Update($USER->GetID(), []);
    }

    public function get(): ?array
    {
        $user = CurrentUser::get();
        $userId = (int) $user->getId();

        if ($userId <= 0) {
            return null;
        }

        return [
            'id' => $userId,
            'login' => (string) $user->getLogin(),
            'email' => (string) $user->getEmail(),
            'first_name' => (string) $user->getFirstName(),
            'last_name' => (string) $user->getLastName(),
            'second_name' => (string) $user->getSecondName(),
            'full_name' => (string) $user->getFullName(),
        ];
    }

    /**
     * @throws AccessDeniedException
     * @throws SystemException
     */
    public function deactive(): void
    {
        $userId = (int) CurrentUser::get()->getId();
        if ($userId <= 0) {
            throw new AccessDeniedException('Пользователь не авторизован.');
        }

        $user = new \CUser();
        if (!$user->Update($userId, ['ACTIVE' => 'N'])) {
            $errorMessage = trim(strip_tags((string) $user->LAST_ERROR)) ?: 'Не удалось деактивировать пользователя.';
            throw new SystemException($errorMessage);
        }

        global $USER;
        if ($USER instanceof \CUser) {
            $USER->Logout();
        }
    }


}