<?php

declare(strict_types=1);

namespace App\Auth;

use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

final class CaptchaActionFilter extends Base
{
    public const ERROR_CAPTCHA_REQUIRED = 'captcha_required';

    private readonly LoginAttempts $loginAttempts;

    public function __construct(
        private readonly int $attemptsLimit = 3,
        ?LoginAttempts $loginAttempts = null,
    ) {
        $this->loginAttempts = $loginAttempts ?? new LoginAttempts();
        parent::__construct();
    }

    public function onBeforeAction(Event $event)
    {
        $request = $this->getAction()->getController()->getRequest();

        $data = $request->isJson()
            ? $request->getJsonList()->toArray()
            : $request->getPostList()->toArray();

        $email = trim((string) ($data['email'] ?? ''));

        if ($email === '' || $this->getLoginAttempts($email) < $this->attemptsLimit) {
            return null;
        }

        global $APPLICATION;

        if (
            $APPLICATION->CaptchaCheckCode(
                (string) ($data['captcha_word'] ?? ''),
                (string) ($data['captcha_sid'] ?? ''),
            )
        ) {
            return null;
        }

        $sid = $APPLICATION->CaptchaGetCode();

        $this->addError(new Error(
            'Введите код с картинки',
            self::ERROR_CAPTCHA_REQUIRED,
            [
                'captcha_sid' => $sid,
                'captcha_url' => '/bitrix/tools/captcha.php?captcha_sid=' . $sid,
            ],
        ));

        return new EventResult(EventResult::ERROR, null, null, $this);
    }

    private function getLoginAttempts(string $email): int
    {
        return $this->loginAttempts->get($email);
    }
}