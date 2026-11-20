<?php
/**
 * Форма авторизации на signa — обёртка над веб-компонентом <auth-form>.
 *
 * Параметры:
 *   BACKURL       — куда вернуться после входа (по умолчанию текущая страница)
 *   ERROR_MESSAGE — сообщение об ошибке, показывается в форме под заголовком
 *   LAST_LOGIN    — подставить в поле e-mail
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

class AuthFormComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        $this->arResult = [
            'BACKURL' => $this->param('BACKURL', $this->currentUrl()),
            'ERROR_MESSAGE' => $this->param('ERROR_MESSAGE'),
            'LAST_LOGIN' => $this->param('LAST_LOGIN'),
        ];

        $this->includeComponentTemplate();

        return $this->arResult;
    }

    private function currentUrl(): string
    {
        global $APPLICATION;

        return (string) $APPLICATION->GetCurPageParam('', ['logout', 'login', 'login_form']);
    }

    private function param(string $name, string $default = ''): string
    {
        $value = $this->arParams['~' . $name] ?? $this->arParams[$name] ?? '';
        $value = trim((string) $value);

        return $value !== '' ? $value : $default;
    }
}