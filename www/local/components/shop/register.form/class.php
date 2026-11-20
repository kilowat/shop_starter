<?php
/**
 * Форма регистрации на signa — обёртка над веб-компонентом <register-form>.
 *
 * Параметры:
 *   BACKURL — куда перейти после успешной регистрации (по умолчанию главная)
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

class RegisterFormComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        $this->arResult = [
            'BACKURL' => $this->param('BACKURL', '/'),
        ];

        $this->includeComponentTemplate();

        return $this->arResult;
    }

    private function param(string $name, string $default = ''): string
    {
        $value = $this->arParams['~' . $name] ?? $this->arParams[$name] ?? '';
        $value = trim((string) $value);

        return $value !== '' ? $value : $default;
    }
}