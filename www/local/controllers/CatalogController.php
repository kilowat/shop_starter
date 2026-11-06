<?php

namespace Controllers;

use App\Catalog\Params;
use App\Catalog\Renderer;
use App\Catalog\Sort;
use Bitrix\Main\Engine\ActionFilter\Authentication;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;

/**
 * Ajax-эндпоинты каталога.
 *
 * GET /api/catalog/filter — живые счётчики фильтра (JSON, без разметки).
 * GET /api/catalog/list   — HTML списка товаров с пагинацией + мета.
 *
 * Оба работают без шапки/подвала: поднимается только пролог Битрикса,
 * компоненты включаются вручную через App\Catalog\Renderer.
 */
final class CatalogController extends Controller
{
    /**
     * Каталог доступен и неавторизованным (счётчики и список — публичные данные).
     */
    public function configureActions(): array
    {
        return [
            'list' => ['-prefilters' => [Authentication::class, Csrf::class]],
            'filter' => ['-prefilters' => [Authentication::class, Csrf::class]],
        ];
    }

    /**
     * Список товаров: html + мета (страница, всего страниц, найдено).
     */
    public function listAction(): array
    {
        $this->includeModules();

        $request = $this->getRequest();
        $sortCode = Sort::resolve((string)$request->getQuery('sort'))['code'];

        // Тихо прогоняем фильтр: он заполняет $GLOBALS['arrFilter'] для списка
        $this->prepareFilter();

        $html = Renderer::component(
            'bitrix:catalog.section',
            'shop_bootstrap',
            Params::section($sortCode, $this->basePage($request))
        );

        return [
            'html' => $html,
        ];
    }

    /**
     * Данные фильтра для <smart-filter>: счётчики по значениям и общее число товаров.
     * Их собирает result_modifier шаблона в $arResult, шаблон отдаёт как JSON.
     */
    public function filterAction(): array
    {
        $this->includeModules();

        $json = Renderer::component(
            'bitrix:catalog.smart.filter',
            'signa',
            Params::filter() + ['RETURN_DATA' => 'Y']
        );

        return [
            'filter' => json_decode($json, true) ?: ['ITEMS' => [], 'TOTAL' => 0],
        ];
    }

    private function includeModules(): void
    {
        Loader::includeModule('iblock');
        Loader::includeModule('catalog');

        // Композитный кеш (StaticArea) кешировал бы готовый HTML компонента
        // и отдавал бы его без выполнения шаблона — вместе с метой это не работает
        if (class_exists(\Bitrix\Main\Composite\Engine::class)) {
            \Bitrix\Main\Composite\Engine::setUseHTMLCache(false);
        }
    }

    /**
     * Включить смарт-фильтр без вывода: нужен ради $GLOBALS['arrFilter']
     * и данных SIGNA_DATA (их складывает result_modifier шаблона).
     */
    /**
     * Адрес настоящей страницы каталога с текущими фильтром и сортировкой —
     * чтобы ссылки пагинации вели на неё, а не на api-эндпоинт.
     */
    private function basePage(\Bitrix\Main\HttpRequest $request): string
    {
        $params = [];

        foreach ($request->getQueryList()->toArray() as $key => $value) {
            if ($key === 'sort' || $key === 'set_filter' || str_starts_with($key, 'arrFilter')) {
                $params[$key] = $value;
            }
        }

        $query = http_build_query($params);

        return Params::LIST_PAGE_URL . ($query !== '' ? '?' . $query : '');
    }

    private function prepareFilter(): void
    {
        // Список ждёт от фильтра только $GLOBALS['arrFilter'] — общий подсчёт не нужен
        Renderer::component('bitrix:catalog.smart.filter', 'signa', Params::filter() + ['SKIP_COUNT' => 'Y']);
    }


}
