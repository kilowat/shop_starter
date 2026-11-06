<?php

namespace Controllers;

use App\Catalog\Sort;
use App\Helper\Component\AjaxController;
use App\Catalog\Components;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Loader;

/**
 * Ajax-эндпоинты каталога (POST).
 *
 * Список: POST /api/catalog/list   — html грида с пагинацией.
 * Фильтр: POST /api/catalog/filter — счётчики по значениям и общее число товаров.
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
     * Фрагмент списка.
     */
    public function listAction(): AjaxJson
    {
        $section = $this->signedParams(Components::LIST);

        if ($section === null) {
            return $this->signatureError();
        }

        $this->renderFilter(); // список читает $GLOBALS['arrFilter'], его заполняет фильтр

        $params = Sort::apply($section['params'], (string)$this->getRequest()->getPost('sort'));
        $params['CURRENT_BASE_PAGE'] = $this->basePage($params);
        $section['params'] = $params;

        return $this->html(Components::LIST, $section);
    }

    /**
     * Счётчики фильтра.
     */
    public function filterAction(): AjaxJson
    {
        $filter = $this->signedParams(Components::FILTER);

        if ($filter === null) {
            return $this->signatureError();
        }

        return $this->result(Components::FILTER, $filter, ['COUNTS', 'TOTAL']);
    }

    protected function prepare(): void
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');

        // фильтр читает значения из $_GET — показываем ему параметры из POST
        $this->exposeFilterParams();

        // готовый HTML компонента не должен кешироваться вместе с шаблоном
        if (class_exists(\Bitrix\Main\Composite\Engine::class)) {
            \Bitrix\Main\Composite\Engine::setUseHTMLCache(false);
        }
    }

    /**
     * Фильтр нужен списку ради $GLOBALS['arrFilter'] — рендерим его молча.
     */
    private function renderFilter(): void
    {
        $filter = $this->signedParams(Components::FILTER, 'filterSigned');

        if ($filter === null) {
            return;
        }

        $this->renderOnly(Components::FILTER, $filter);
    }


    /**
     * Смарт-фильтр берёт значения из $_GET (если в запросе есть set_filter),
     * поэтому параметры фильтра из POST показываем ему в $_GET.
     */
    private function exposeFilterParams(): void
    {
        foreach ($this->getRequest()->getPostList()->toArray() as $key => $value) {
            if ($key === 'set_filter' || $key === 'del_filter' || str_starts_with($key, 'arrFilter')) {
                $_GET[$key] = $value;
            }
        }
    }

    /**
     * Адрес настоящей страницы с текущими фильтром и сортировкой (без PAGEN_1).
     */
    private function basePage(array $params): string
    {
        $path = (string)(parse_url((string)($params['CURRENT_BASE_PAGE'] ?? '/'), PHP_URL_PATH) ?: '/');
        $query = [];

        foreach ($this->getRequest()->getPostList()->toArray() as $key => $value) {
            if ($key === 'sort' || $key === 'set_filter' || str_starts_with($key, 'arrFilter')) {
                $query[$key] = $value;
            }
        }

        $search = http_build_query($query);

        return $path . ($search !== '' ? '?' . $search : '');
    }
}
