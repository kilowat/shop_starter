<?php

namespace Controllers;

use App\Auth\Dto\UpdateProfileRequest;
use App\Auth\ProfileService;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Validation\Engine\AutoWire\ValidationParameter;

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
        $protected = [
            new ActionFilter\Authentication(),
            new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
            new ActionFilter\Csrf(),
        ];

        return [
            'update' => ['prefilters' => $protected],
            'passwordChange' => ['prefilters' => $protected],
            'delete' => ['prefilters' => $protected],
        ];
    }

    public function getAutoWiredParameters(): array
    {
        return [
            new ValidationParameter(
                UpdateProfileRequest::class,
                fn() => UpdateProfileRequest::fromRequest($this->getRequest()),
            ),
        ];
    }

    /**
     * Обновить имя и/или телефон текущего пользователя.
     */
    public function updateAction(UpdateProfileRequest $request): void
    {
        $this->profile->updateProfile($request);
    }

    /**
     * Смена пароля: письмо с контрольным словом, далее auth/password/reset.
     */
    public function passwordChangeAction(): void
    {
        $this->profile->requestPasswordChange();
    }

    /**
     * Мягкое удаление профиля: деактивация и выход.
     */
    public function deleteAction(): void
    {
        $this->profile->deleteProfile();
    }
}