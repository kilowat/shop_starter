<?php

namespace Controllers;

use App\Catalog\Sort;
use App\Helper\Component\AjaxController;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Loader;

/**
 * Ajax-эндпоинты каталога (POST).
 *
 * Фильтр и сортировка приходят в строке запроса (их читает смарт-фильтр),
 * подпись параметров и страница — в теле запроса.
 */
final class CatalogController extends AjaxController
{
    /**
     * Каталог доступен всем: снимаем проверку авторизации, CSRF остаётся.
     */
    public function configureActions(): array
    {
        return [
            'list' => ['-prefilters' => [Authentication::class]],
            'filter' => ['-prefilters' => [Authentication::class]],
        ];
    }

    /**
     * Фрагмент списка товаров.
     */
    public function listAction(): AjaxJson
    {
        $section = $this->signedParams('bitrix:catalog.section');

        if ($section === null) {
            return $this->signatureError();
        }

        // фильтр нужен списку: он заполняет $GLOBALS['arrFilter']
        $this->renderSigned('bitrix:catalog.smart.filter', 'filterSigned');

        $section['params'] = Sort::apply($section['params'], (string)$this->getRequest()->getQuery('sort'));

        return $this->html('bitrix:catalog.section', $section);
    }

    /**
     * Счётчики по значениям фильтра и общее число товаров.
     */
    public function filterAction(): AjaxJson
    {
        $filter = $this->signedParams('bitrix:catalog.smart.filter');

        return $filter === null
            ? $this->signatureError()
            : $this->result('bitrix:catalog.smart.filter', $filter, ['COUNTS', 'TOTAL']);
    }

    protected function prepare(): void
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');

        // готовый HTML компонента не должен кешироваться вместе с шаблоном
        if (class_exists(\Bitrix\Main\Composite\Engine::class)) {
            \Bitrix\Main\Composite\Engine::setUseHTMLCache(false);
        }
    }
}
