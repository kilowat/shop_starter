<?php

namespace App\Helper\Component;

use Bitrix\Main\Component\ParameterSigner;

/**
 * Подпись параметров компонента для ajax-запросов.
 *
 * Клиент возвращает токен обратно, контроллер проверяет подпись и получает
 * ровно те параметры, с которыми компонент отрисован на странице.
 * Подделать или подменить значения нельзя.
 *
 * Вспомогательный класс ничего не знает о конкретных компонентах проекта:
 * какие параметры подписывать — решает вызывающий код (третий аргумент $keys).
 */
final class Signer
{
    /** Имя параметра компонента, в котором лежит токен. */
    public const PARAM = 'SIGNED_PARAMS';

    /**
     * Подписать параметры компонента.
     *
     * Подписывается ровно тот массив, который передали при вызове компонента:
     * списки параметров вести не нужно, новые настройки попадают в подпись сами.
     *
     * @param string $component имя компонента, например bitrix:news.list
     * @param string $template  имя шаблона
     * @param array  $params    параметры компонента
     *
     * @return string токен для разметки
     */
    public static function sign(string $component, string $template, array $params): string
    {
        return ParameterSigner::signParameters($component, [
            'template' => $template,
            'params' => $params,
        ]);
    }

    /**
     * Добавить к параметрам компонента подпись (для вызова на странице
     * и для повторного рендера в ajax).
     */
    public static function attach(string $component, string $template, array $params): array
    {
        $params[self::PARAM] = self::sign($component, $template, $params);

        return $params;
    }

    /**
     * Проверить подпись и вернуть ['template' => ..., 'params' => [...]].
     *
     * @throws \Bitrix\Main\Security\Sign\BadSignatureException
     */
    public static function unsign(string $component, string $signed): array
    {
        $data = ParameterSigner::unsignParameters($component, $signed);

        return [
            'template' => (string)($data['template'] ?? ''),
            'params' => (array)($data['params'] ?? []),
        ];
    }
}
