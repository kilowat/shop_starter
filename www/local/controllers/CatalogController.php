<?php
namespace Controllers;

use Bitrix\Main\Engine\Controller;
use Lib\AjaxHtmlResponse;

final class CatalogController extends Controller
{
    protected function getDefaultPreFilters(): array
    {
        return [

        ];
    }

    public function indexAction()
    {
        return new AjaxHtmlResponse(viewPath: '/catalog/_products_list.php');
    }
}
