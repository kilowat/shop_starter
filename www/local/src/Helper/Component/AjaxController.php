<?php

namespace App\Helper\Component;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Engine\Response\ContentArea\Component;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Security\Sign\BadSignatureException;

/**
 * База для ajax-обновления компонентов.
 *
 * Любой контроллер наследует этот класс и описывает свои экшены:
 * берёт параметры из подписи (signedParams), при необходимости дополняет
 * их значениями пользователя и отдаёт html или данные компонента
 * (html / result). Новый контроллер для другой страницы — это десяток строк:
 *
 *     final class NewsController extends AjaxController
 *     {
 *         public function listAction(): AjaxJson
 *         {
 *             $signed = $this->signedParams('bitrix:news.list');
 *
 *             return $signed === null
 *                 ? $this->signatureError()
 *                 : $this->html('bitrix:news.list', $signed);
 *         }
 *     }
 */
abstract class AjaxController extends Controller
{
    /**
     * Подготовка окружения перед рендером: модули, отключение кеша страницы и т.п.
     */
    protected function prepare(): void
    {
    }

    /**
     * Ответ с HTML компонента.
     *
     * @param array $signed то, что вернул signedParams()
     */
    final protected function html(string $component, array $signed): AjaxJson
    {
        return AjaxJson::createSuccess([
            'html' => $this->render($component, $signed)->getHtml(),
        ]);
    }

    /**
     * Ответ с выбранными ключами $arResult компонента (html в ответ не попадает).
     *
     * @param array $signed   то, что вернул signedParams()
     * @param array $dataKeys ключи $arResult, например ['COUNTS', 'TOTAL']
     */
    final protected function result(string $component, array $signed, array $dataKeys): AjaxJson
    {
        $renderer = $this->render($component, $signed, $dataKeys);
        $renderer->getHtml();

        return AjaxJson::createSuccess([
            'result' => $renderer->getSectionData(),
        ]);
    }

    /**
     * Отрендерить компонент без ответа — например, чтобы он заполнил свои глобальные данные.
     */
    final protected function renderOnly(string $component, array $signed): void
    {
        $this->render($component, $signed)->getHtml();
    }

    /**
     * Параметры компонента из подписанного токена.
     *
     * @param string $postKey имя поля в POST с токеном
     *
     * @return array{template: string, params: array}|null null, если подпись не наша
     */
    final protected function signedParams(string $component, string $postKey = 'signed'): ?array
    {
        try {
            $signed = Signer::unsign($component, (string)$this->getRequest()->getPost($postKey));
        } catch (BadSignatureException $e) {
            return null;
        }

        return $signed['template'] !== '' ? $signed : null;
    }

    /**
     * Ответ «параметры запроса не подписаны».
     */
    final protected function signatureError(): AjaxJson
    {
        $errors = new ErrorCollection();
        $errors->add([new Error('Параметры запроса не подписаны', 'invalid_signed_params')]);

        return AjaxJson::createError($errors);
    }

    private function render(string $component, array $signed, array $dataKeys = []): Component
    {
        $this->prepare();

        $template = (string)$signed['template'];
        $params = Signer::attach($component, $template, (array)$signed['params']);

        return new Component($component, $template, $params, $dataKeys);
    }
}
