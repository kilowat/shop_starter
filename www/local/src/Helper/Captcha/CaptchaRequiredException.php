<?php

declare(strict_types=1);

namespace App\Heper;

use Bitrix\Main\SystemException;

/**
 * Вход отклонён из-за неверного кода капчи (политика попыток входа).
 */
class CaptchaRequiredException extends SystemException
{
}