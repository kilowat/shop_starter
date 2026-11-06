<?php

namespace App\Catalog;

/**
 * Включение компонента с захватом HTML.
 *
 * Нужен ajax-эндпоинтам: они отдают только кусок разметки, без шапки,
 * подвала и шаблона сайта.
 */
final class Renderer
{
    /**
     * Отрисовать компонент и вернуть его HTML.
     */
    public static function component(string $name, string $template, array $params): string
    {
        global $APPLICATION;

        ob_start();

        try {
            $APPLICATION->IncludeComponent(
                $name,
                $template,
                $params,
                false,
                ['HIDE_ICONS' => 'Y']
            );
        } catch (\Throwable $e) {
            ob_end_clean();

            throw $e;
        }

        return (string)ob_get_clean();
    }
}
