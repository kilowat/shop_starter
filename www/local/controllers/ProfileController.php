<?php

namespace Controllers;

use App\Auth\Dto\ProfilePayload;
use App\Auth\ProfileService;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Validation\Engine\AutoWire\ValidationParameter;

/**
 * Профиль текущего пользователя.
 */
final class ProfileController extends ApiController
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
            'index' => ['prefilters' => [new ActionFilter\Authentication()]],
            'update' => ['prefilters' => $protected],
            'delete' => ['prefilters' => $protected],
        ];
    }

    public function getAutoWiredParameters(): array
    {
        return [
            new ValidationParameter(
                ProfilePayload::class,
                fn() => ProfilePayload::fromRequest($this->getRequest()),
            ),
        ];
    }

    /**
     * Обновить имя и/или телефон текущего пользователя.
     */
    public function updateAction(ProfilePayload $payload): void
    {
        $this->profile->update($payload);
    }
    /**
     * Мягкое удаление профиля: деактивация и выход.
     */
    public function deleteAction(): void
    {
        $this->profile->deactive();
    }
}