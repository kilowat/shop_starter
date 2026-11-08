<?php
/**
 * Общий расчёт данных для шаблонов bitrix:system.pagenavigation.
 *
 * Подключается из каждого шаблона через require_once, поэтому повторный
 * вывод двух пагинаторов на странице не приводит к повторному объявлению.
 *
 * Никаких параметров компонента здесь нет: подписи, селекторы и настройки
 * ленивой загрузки живут прямо в шаблонах пагинатора.
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

final class ShopPaginationTemplate
{
    /**
     * Показывать ли пагинацию (повторяет условие ядра system.pagenavigation).
     */
    public static function shouldShow(array $arResult): bool
    {
        if (!empty($arResult['NavShowAlways'])) {
            return (int)($arResult['NavPageCount'] ?? 0) > 0;
        }

        return (int)($arResult['NavRecordCount'] ?? 0) > 0
            && ((int)($arResult['NavPageCount'] ?? 0) > 1 || !empty($arResult['NavShowAll']));
    }

    /**
     * @param array $arResult arResult компонента system.pagenavigation
     *
     * @return array{
     *     page:int,total:int,pageParam:string,nextPage:int,prevPage:int,
     *     nextUrl:string,prevUrl:string,url:callable,pages:array,
     *     hasPrev:bool,hasNext:bool
     * }
     */
    public static function prepare(array $arResult): array
    {
        $total = max(1, (int)($arResult['NavPageCount'] ?? 1));
        $page = max(1, min($total, (int)($arResult['NavPageNomer'] ?? 1)));
        // NavNum может быть не числом (например, случайная строка в некоторых
        // компонентах), поэтому оставляем только безопасные символы.
        $navNum = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($arResult['NavNum'] ?? '1')) ?: '1';
        $pageParam = 'PAGEN_' . $navNum;
        $path = (string)($arResult['sUrlPath'] ?? '');
        $query = htmlspecialcharsback((string)($arResult['NavQueryString'] ?? ''));
        $savePage = !empty($arResult['bSavePage']);
        $desc = !empty($arResult['bDescPageNumbering']);

        // URL конкретной страницы (внутренний номер страницы, как в ядре).
        $url = static function (int $target) use ($path, $query, $pageParam, $savePage): string {
            $params = $query;

            if ($target > 1 || $savePage) {
                $params .= ($params !== '' ? '&' : '') . $pageParam . '=' . $target;
            }

            return $path . ($params !== '' ? '?' . $params : '');
        };

        // В обратной нумерации «следующая» страница для пользователя — это
        // внутренний номер на единицу меньше, и наоборот.
        $prevPage = $desc ? $page + 1 : $page - 1;
        $nextPage = $desc ? $page - 1 : $page + 1;
        $hasPrev = $desc ? $page < $total : $page > 1;
        $hasNext = $desc ? $page > 1 : $page < $total;

        $start = (int)($arResult['nStartPage'] ?? 1);
        $end = (int)($arResult['nEndPage'] ?? $start);
        $pages = [];

        $addPage = static function (int $number) use (&$pages, $total, $page, $desc): void {
            $pages[] = [
                'page' => $number,
                'display' => $desc ? ($total - $number + 1) : $number,
                'current' => $number === $page,
            ];
        };

        if (!$desc) {
            if ($start > 1) {
                $addPage(1);

                if ($start > 2) {
                    $pages[] = null;
                }
            }

            for ($number = $start; $number <= $end; $number++) {
                $addPage($number);
            }

            if ($end < $total) {
                if ($end < $total - 1) {
                    $pages[] = null;
                }

                $addPage($total);
            }
        } else {
            if ($start < $total) {
                $addPage($total);

                if ($start < $total - 1) {
                    $pages[] = null;
                }
            }

            for ($number = $start; $number >= $end; $number--) {
                $addPage($number);
            }

            if ($end > 1) {
                if ($end > 2) {
                    $pages[] = null;
                }

                $addPage(1);
            }
        }

        return [
            'page' => $page,
            'total' => $total,
            'pageParam' => $pageParam,
            'nextPage' => $nextPage,
            'prevPage' => $prevPage,
            'nextUrl' => $hasNext ? $url($nextPage) : '',
            'prevUrl' => $hasPrev ? $url($prevPage) : '',
            'url' => $url,
            'pages' => $pages,
            'hasPrev' => $hasPrev,
            'hasNext' => $hasNext,
        ];
    }

    /**
     * Экранирование значения для HTML-атрибута или текста.
     */
    public static function e(mixed $value): string
    {
        return htmlspecialcharsbx((string)$value);
    }
}
