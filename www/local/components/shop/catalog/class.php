<?php
/**
 * Каталог: смарт-фильтр + сортировка + список товаров в одном компоненте.
 *
 * AJAX приходит на тот же URL, что и страница (POST + ajax_action + sessid),
 * и обрабатывается здесь же до вывода шаблона:
 *   list   — HTML списка (грид + пагинация);
 *   filter — счётчики фильтра (COUNTS, TOTAL).
 *
 * Вложенные компоненты подключаются с родителем $this, поэтому их шаблоны
 * можно переопределять в папке шаблона shop:catalog, а стандартный поиск
 * шаблонов (shop_bootstrap, signa) продолжает работать.
 *
 * Постраничная навигация — отдельные шаблоны bitrix:system.pagenavigation
 * (classic / show_more / lazy), выбор — параметр PAGINATION_TEMPLATE.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Context;
use Bitrix\Main\Web\Json;

class ShopCatalogComponent extends CBitrixComponent
{
    /** @var array|null Результат shop:catalog.sort, заполняется renderSort(). */
    private ?array $sort = null;

    public function onPrepareComponentParams($params)
    {
        $params['IBLOCK_TYPE'] = trim((string) ($params['IBLOCK_TYPE'] ?? '')) ?: 'catalog';
        $params['IBLOCK_ID'] = (int) ($params['IBLOCK_ID'] ?? 0);
        $params['FILTER_NAME'] = trim((string) ($params['FILTER_NAME'] ?? '')) ?: 'arrFilter';
        $params['PAGE_ELEMENT_COUNT'] = (int) ($params['PAGE_ELEMENT_COUNT'] ?? 0) ?: 12;
        $params['PRICE_CODE'] = $this->normalizePriceCodes($params['PRICE_CODE'] ?? null);
        $params['CACHE_TIME'] = (int) ($params['CACHE_TIME'] ?? 3600);

        // Постраничная навигация — отдельный шаблон bitrix:system.pagenavigation.
        // classic   — обычные ссылки, переход с полной перезагрузкой страницы;
        // show_more — кнопка «Показать ещё», контент дописывается AJAX-ом;
        // lazy      — бесконечная прокрутка без видимой пагинации.
        $params['PAGINATION_TEMPLATE'] = trim((string) ($params['PAGINATION_TEMPLATE'] ?? '')) ?: 'classic';

        $params['SORT_FIELDS'] = $this->normalizeSortFields($params['SORT_FIELDS'] ?? null);
        $params['SORT_DEFAULT'] = $this->normalizeSortDefault(
            $params['SORT_DEFAULT'] ?? null,
            $params['SORT_FIELDS']
        );
        $params['SORT_PARAM_NAME'] = trim((string) ($params['SORT_PARAM_NAME'] ?? '')) ?: 'sort';
        $params['SORT_TITLE'] = trim((string) ($params['SORT_TITLE'] ?? '')) ?: 'Сортировка';

        return $params;
    }

    public function executeComponent()
    {
        $this->resetFilter();

        $request = Context::getCurrent()->getRequest();
        $action = $request->isPost() ? $request->getPost('ajax_action') : null;

        if (is_string($action) && $action !== '') {
            $this->handleAjax($action);
        }

        $this->includeComponentTemplate();
    }

    /* ---------- AJAX ---------- */

    private function handleAjax(string $action): never
    {
        global $APPLICATION;

        $APPLICATION->RestartBuffer();

        if (!check_bitrix_sessid()) {
            $this->json(['status' => 'error', 'message' => 'Session expired'], 403);
        }

        try {
            $data = match ($action) {
                'list' => ['html' => $this->ajaxList()],
                'filter' => ['result' => $this->ajaxFilter()],
                default => null,
            };
        } catch (\Throwable $e) {
            AddMessage2Log(
                $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
                'catalog'
            );
            $this->json(['status' => 'error', 'message' => 'Unable to render response'], 500);
        }

        if ($data === null) {
            $this->json(['status' => 'error', 'message' => 'Unknown action'], 400);
        }

        $this->json(['status' => 'success', 'data' => $data]);
    }

    private function ajaxList(): string
    {
        ob_start();
        $this->renderSort();               // заполняет $this->sort
        $this->renderFilter(false, true);  // заполняет $GLOBALS[FILTER_NAME]; TOTAL списку не нужен
        ob_clean();                        // выбрасываем HTML сортировки и фильтра

        $this->renderList();

        return (string) ob_get_clean();
    }

    private function ajaxFilter(): array
    {
        ob_start();
        $result = $this->renderFilter(true);
        ob_end_clean();

        return is_array($result)
            ? array_intersect_key($result, ['COUNTS' => 1, 'TOTAL' => 1])
            : [];
    }

    private function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        echo Json::encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        die();
    }

    /* ---------- Вложенные компоненты (публичные: вызываются из шаблона) ---------- */

    public function renderSort(): void
    {
        global $APPLICATION;

        $result = $APPLICATION->IncludeComponent(
            'shop:catalog.sort',
            '',
            [
                'FIELDS' => $this->arParams['SORT_FIELDS'],
                'DEFAULT' => $this->arParams['SORT_DEFAULT'],
                'PARAM_NAME' => $this->arParams['SORT_PARAM_NAME'],
                'TITLE' => $this->arParams['SORT_TITLE'],
                'SHOW_TITLE' => 'Y',
            ],
            $this,
            ['HIDE_ICONS' => 'Y'],
            true
        );

        $this->sort = is_array($result) ? $result : null;
    }

    /**
     * @param bool $returnResult Вернуть arResult вместо вывода HTML.
     * @param bool $skipCount    Не считать общее число товаров (нужно только фильтру).
     */
    public function renderFilter(bool $returnResult = false, bool $skipCount = false): mixed
    {
        global $APPLICATION;

        return $APPLICATION->IncludeComponent(
            'bitrix:catalog.smart.filter',
            'signa',
            [
                'IBLOCK_TYPE' => $this->arParams['IBLOCK_TYPE'],
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'SECTION_ID' => '',
                'FILTER_NAME' => $this->arParams['FILTER_NAME'],
                'PRICE_CODE' => $this->arParams['PRICE_CODE'],
                'CACHE_TYPE' => 'A',
                'CACHE_TIME' => '36000000',
                'CACHE_GROUPS' => 'Y',
                'SAVE_IN_SESSION' => 'N',
                'DISPLAY_ELEMENT_COUNT' => 'Y',
                'XML_EXPORT' => 'N',
                'CONVERT_CURRENCY' => 'N',
                'HIDE_NOT_AVAILABLE' => 'N',
                'SKIP_COUNT' => $skipCount ? 'Y' : 'N',
            ],
            $this,
            ['HIDE_ICONS' => 'Y'],
            $returnResult
        );
    }

    public function renderList(): void
    {
        global $APPLICATION;

        // Если список вызван раньше сортировки — получаем её без вывода
        if ($this->sort === null) {
            ob_start();
            $this->renderSort();
            ob_end_clean();
        }

        $sort = is_array($this->sort['SORT'] ?? null) ? $this->sort['SORT'] : [];

        $APPLICATION->IncludeComponent(
            'bitrix:catalog.section',
            'shop_bootstrap',
            [
                'IBLOCK_TYPE' => $this->arParams['IBLOCK_TYPE'],
                'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                'FILTER_NAME' => $this->arParams['FILTER_NAME'],
                'PRICE_CODE' => $this->arParams['PRICE_CODE'],
                'PAGE_ELEMENT_COUNT' => $this->arParams['PAGE_ELEMENT_COUNT'],
                'CACHE_TIME' => $this->arParams['CACHE_TIME'],

                'SECTION_ID' => '',
                'SECTION_CODE' => '',
                'SHOW_ALL_WO_SECTION' => 'Y',
                'INCLUDE_SUBSECTIONS' => 'Y',
                'PRICE_VAT_INCLUDE' => 'Y',
                'CONVERT_CURRENCY' => 'N',
                'CACHE_TYPE' => 'A',
                'CACHE_GROUPS' => 'Y',
                'CACHE_FILTER' => 'Y',
                'LINE_ELEMENT_COUNT' => '3',
                'ELEMENT_SORT_FIELD' => $sort['FIELD'] ?? 'sort',
                'ELEMENT_SORT_ORDER' => $sort['ORDER'] ?? 'asc',
                'ELEMENT_SORT_FIELD2' => $sort['FIELD2'] ?? 'id',
                'ELEMENT_SORT_ORDER2' => $sort['ORDER2'] ?? 'desc',
                'PROPERTY_CODE' => [],
                'OFFERS_LIMIT' => '5',
                'DETAIL_URL' => '/catalog/#SECTION_CODE#/#ELEMENT_CODE#/',
                'SECTION_URL' => '/catalog/#SECTION_CODE#/',
                'SHOW_OLD_PRICE' => 'Y',
                'SHOW_DISCOUNT_PERCENT' => 'Y',
                'USE_PRICE_COUNT' => 'N',
                'PRODUCT_SUBSCRIPTION' => 'N',
                'DISPLAY_COMPARE' => 'N',
                'ADD_PICT_PROP' => '-',
                'LABEL_PROP' => '-',
                'ADD_SECTIONS_CHAIN' => 'N',
                'SET_TITLE' => 'N',
                'SET_BROWSER_TITLE' => 'N',
                'SET_META_KEYWORDS' => 'N',
                'SET_META_DESCRIPTION' => 'N',
                'SET_LAST_MODIFIED' => 'N',
                'META_KEYWORDS' => '-',
                'META_DESCRIPTION' => '-',
                'BROWSER_TITLE' => '-',
                'COMPATIBLE_MODE' => 'N',
                'DISABLE_INIT_JS_IN_COMPONENT' => 'Y',
                'PAGER_TEMPLATE' => $this->arParams['PAGINATION_TEMPLATE'],
                'PAGER_TITLE' => 'Товары',
                'PAGER_SHOW_ALWAYS' => 'N',
                'DISPLAY_TOP_PAGER' => 'N',
                'DISPLAY_BOTTOM_PAGER' => 'Y',
            ],
            $this,
            ['HIDE_ICONS' => 'Y']
        );
    }

    /* ---------- Вспомогательное ---------- */

    private function resetFilter(): void
    {
        $GLOBALS[$this->arParams['FILTER_NAME']] = [];
    }

    /**
     * Варианты сортировки: массив или JSON/сериализованная строка.
     *
     * Элемент описывается ключами:
     *   CODE  — значение в URL (обязателен);
     *   NAME  — подпись (обязателен);
     *   FIELD — поле сортировки catalog.section (обязателен);
     *   ORDER — asc|desc;
     *   FIELD2 / ORDER2 — необязательная вторая сортировка.
     *
     * Некорректные элементы отбрасываются; если не осталось ни одного —
     * берётся стандартный набор.
     */
    private function normalizeSortFields(mixed $value): array
    {
        if (is_string($value)) {
            $value = trim($value) !== '' ? $this->decodeSortFields($value) : null;
        }

        $fields = [];

        foreach (is_array($value) ? $value : [] as $item) {
            if (!is_array($item)) {
                continue;
            }

            $code = trim((string) ($item['CODE'] ?? ''));
            $name = trim((string) ($item['NAME'] ?? ''));
            $field = trim((string) ($item['FIELD'] ?? ''));

            if ($code === '' || $name === '' || $field === '') {
                continue;
            }

            $fields[] = [
                'CODE' => $code,
                'NAME' => $name,
                'FIELD' => $field,
                'ORDER' => strtolower(trim((string) ($item['ORDER'] ?? 'asc'))) === 'desc' ? 'desc' : 'asc',
                'FIELD2' => trim((string) ($item['FIELD2'] ?? '')) ?: 'id',
                'ORDER2' => strtolower(trim((string) ($item['ORDER2'] ?? 'desc'))) === 'asc' ? 'asc' : 'desc',
            ];
        }

        return $fields;
    }

    private function decodeSortFields(string $value): ?array
    {
        $decoded = json_decode($value, true);

        if (!is_array($decoded)) {
            $decoded = @unserialize($value, ['allowed_classes' => false]);
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * SORT_DEFAULT должен совпадать с CODE одного из вариантов,
     * иначе берём первый код.
     */
    private function normalizeSortDefault(mixed $value, array $fields): string
    {
        $value = trim((string) $value);
        $codes = array_column($fields, 'CODE');

        return in_array($value, $codes, true) ? $value : (string) ($codes[0] ?? 'default');
    }

    /**
     * Коды типов цен: массив, строка "BASE" или "BASE,USD".
     */
    private function normalizePriceCodes(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/\s*,\s*/', trim($value)) ?: [];
        }

        $codes = [];

        foreach ((array) $value as $code) {
            $code = trim((string) $code);

            if ($code !== '' && !in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        return $codes !== [] ? $codes : ['BASE'];
    }
}
