<?php

namespace Controllers;

use App\Auth\ProfileService;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;

final class ProfileController extends Controller
{
    private ProfileService $profile;

    protected function init(): void
    {
        $this->profile = new ProfileService();

        parent::init();
    }

    protected function getDefaultPreFilters(): array
    {
        return [];
    }

    public function configureActions(): array
    {
        return [
            'update' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'passwordChange' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
            'delete' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                    new ActionFilter\Csrf(),
                ],
            ],
        ];
    }

    /**
     * Обновить имя и/или телефон текущего пользователя.
     */
    public function updateAction(string $name = '', string $phone = ''): array
    {
        return $this->profile->updateProfile($name, $phone);
    }

    /**
     * Смена пароля: письмо с контрольным словом, далее auth/password/reset.
     */
    public function passwordChangeAction(): array
    {
        return $this->profile->requestPasswordChange();
    }

    /**
     * Мягкое удаление профиля: деактивация и выход.
     */
    public function deleteAction(): array
    {
        return $this->profile->deleteProfile();
    }
}
