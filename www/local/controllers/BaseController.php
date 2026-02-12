<?php
namespace Controllers;
use Bitrix\Main\Engine\Controller;

abstract class BaseController extends Controller
{
    public function init()
    {
        parent::init();
    }

    protected function getDefaultPreFilters(): array
    {
        return [
 
        ];
    }

}
