<?php
/**
 * Описание одного AJAX-действия.
 */

namespace App\Helper;

use Closure;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

final class CatalogAjaxAction
{
    /**
     * @param array<int, string> $prepare    include-файлы, выполняемые до рендера
     * @param array<int, string> $resultKeys ключи arResult для response=result
     */
    public function __construct(
        public readonly string $render,
        public readonly array $prepare = [],
        public readonly ?Closure $init = null,
        public readonly bool $returnResult = false,
        public readonly string $response = CatalogAjax::RESPONSE_HTML,
        public readonly array $resultKeys = [],
    ) {
    }
}
