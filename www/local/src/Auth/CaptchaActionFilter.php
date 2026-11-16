<?php

declare(strict_types=1);

namespace App\Auth;

use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

/**
 * Владеет механизмом попыток по e-mail: проверяет счётчик и требует капчу
 * после лимита (onBeforeAction), засчитывает попытку перед выполнением действия
 * и сбрасывает счётчик при успехе (onAfterAction). Если действие выбросило
 * исключение, ядро не вызывает onAfterAction — попытка остаётся засчитанной.
 * Сервисы и контроллер о механизме попыток не знают.
 */
final class CaptchaActionFilter extends Base
{
    public const ERROR_CAPTCHA_REQUIRED = 'captcha_required';

    private readonly LoginAttempts $loginAttempts;

    public function __construct(
        private readonly int $attemptsLimit = 3,
        ?LoginAttempts $loginAttempts = null,
    ) {
        parent::__construct();
        $this->loginAttempts = $loginAttempts ?? new LoginAttempts();
    }

    public function onBeforeAction(Event $event)
    {
        $data = $this->getRequestData();
        $email = trim((string) ($data['email'] ?? ''));

        if ($email === '') {
            return null;
        }

        if ($this->loginAttempts->get($email) >= $this->attemptsLimit && !$this->checkCaptcha($data)) {
            $sid = $this->generateCaptcha();

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

        $this->loginAttempts->hit($email);

        return null;
    }

    public function onAfterAction(Event $event)
    {
        $controller = $event->getParameter('controller');

        if (!$controller instanceof Controller || $controller->getErrors() !== []) {
            return null;
        }

        $email = trim((string) ($this->getRequestData()['email'] ?? ''));

        if ($email !== '') {
            $this->loginAttempts->clear($email);
        }

        return null;
    }

    private function getRequestData(): array
    {
        $request = $this->getAction()->getController()->getRequest();

        return $request->isJson()
            ? $request->getJsonList()->toArray()
            : $request->getPostList()->toArray();
    }

    private function checkCaptcha(array $data): bool
    {
        global $APPLICATION;

        return $APPLICATION->CaptchaCheckCode(
            (string) ($data['captcha_word'] ?? ''),
            (string) ($data['captcha_sid'] ?? ''),
        );
    }

    private function generateCaptcha(): string
    {
        global $APPLICATION;

        return $APPLICATION->CaptchaGetCode();
    }
}
