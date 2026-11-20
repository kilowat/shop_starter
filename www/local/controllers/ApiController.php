<?php

namespace Controllers;

use App\Auth\AuthException;
use App\Helper\Captcha\CaptchaRequiredException;
use Bitrix\Main\AccessDeniedException;
use Bitrix\Main\Engine\AutoWire\BinderArgumentException;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Response;
use Bitrix\Main\SystemException;
use Bitrix\Main\Validation\ValidationException;

abstract class ApiController extends Controller
{
    private ?int $errorStatus = null;

    protected function runProcessingThrowable(\Throwable $e): void
    {
        if ($e instanceof BinderArgumentException || $e instanceof ValidationException) {
            parent::runProcessingThrowable($e);
            $this->errorStatus = 400;

            return;
        }

        [$code, $message, $status] = $this->mapException($e);
        $this->addError(new Error($message, $code));
        $this->errorStatus = $status;
    }

    /**
     * @return array{0: string, 1: string, 2: int}
     */
    private function mapException(\Throwable $e): array
    {
        return match (true) {
            $e instanceof AccessDeniedException => ['ACCESS_DENIED', $e->getMessage(), 403],
            $e instanceof CaptchaRequiredException => ['CAPTCHA_REQUIRED', $e->getMessage(), 400],
            $e instanceof AuthException => ['AUTH_ERROR', $e->getMessage(), 400],
            $e instanceof SystemException => ['BUSINESS_ERROR', $e->getMessage(), 400],
            default => ['INTERNAL_ERROR', 'Внутренняя ошибка сервера', 500],
        };
    }

    public function finalizeResponse(Response $response): void
    {
        if ($this->errorStatus !== null && $response instanceof HttpResponse) {
            $response->setStatus($this->errorStatus);
        }
    }
}