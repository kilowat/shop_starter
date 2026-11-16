<?php

declare(strict_types=1);

namespace App\Helper\Captcha;

use Bitrix\Main\Engine\ActionFilter\Base;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

/**
 * Требует валидную капчу (captcha_word + captcha_sid) в каждом POST-запросе
 * обёрнутого экшена. Без счётчиков и лимитов: капчи нет или она невалидна —
 * ошибка captcha_required со свежим sid. GET-запросы пропускает.
 */
final class CaptchaActionFilter extends Base
{
    public const ERROR_CAPTCHA_REQUIRED = 'captcha_required';

    private readonly CaptchaService $captcha;

    public function __construct()
    {
        parent::__construct();

        $this->captcha = new CaptchaService();
    }

    public function onBeforeAction(Event $event)
    {
        $request = $this->getAction()->getController()->getRequest();

        if (!$request->isPost()) {
            return null;
        }

        $data = $request->isJson()
            ? $request->getJsonList()->toArray()
            : $request->getPostList()->toArray();

        if ($this->captcha->check(
            (string) ($data['captcha_word'] ?? ''),
            (string) ($data['captcha_sid'] ?? ''),
        )) {
            return null;
        }

        $this->addError(new Error(
            'Введите код с картинки',
            self::ERROR_CAPTCHA_REQUIRED,
            $this->captcha->generate(),
        ));

        return new EventResult(EventResult::ERROR, null, null, $this);
    }
}
