<?php
/**
 * Ссылка пользователя в шапке: личный кабинет или авторизация.
 *
 * Параметры:
 *   PERSONAL_URL, PERSONAL_TEXT — адрес и подпись личного кабинета
 *   AUTH_URL, AUTH_TEXT         — адрес и подпись страницы авторизации
 *   SHOW_NAME                   — показывать имя пользователя вместо подписи
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

class UserLinkComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        global $APPLICATION;

        $user = $GLOBALS['USER'];
        $authorized = $user instanceof CUser && $user->IsAuthorized();

        $this->arResult = [
            'AUTHORIZED' => $authorized,
            'LINK' => $authorized
                ? $this->param('PERSONAL_URL', '/personal/')
                : $this->param('AUTH_URL', '/auth/?login=yes'),
            'TEXT' => $authorized
                ? $this->param('PERSONAL_TEXT', 'Личный кабинет')
                : $this->param('AUTH_TEXT', 'Авторизоваться'),
            'BACKURL' => (string) $APPLICATION->GetCurPageParam('', ['logout', 'login', 'login_form']),
        ];

        if ($authorized && $this->param('SHOW_NAME', 'N') === 'Y') {
            $this->arResult['TEXT'] = $this->userName($user) ?: $this->arResult['TEXT'];
        }

        $this->includeComponentTemplate();

        return $this->arResult;
    }

    private function userName(CUser $user): string
    {
        $name = trim((string) $user->GetFullName());

        return $name !== '' ? $name : trim((string) $user->GetLogin());
    }

    /** Берёт «сырое» значение (~NAME), иначе обработанное; пустое заменяет на $default */
    private function param(string $name, string $default = ''): string
    {
        $value = $this->arParams['~' . $name] ?? $this->arParams[$name] ?? '';
        $value = trim((string) $value);

        return $value !== '' ? $value : $default;
    }
}