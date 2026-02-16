<?
namespace Local\Controllers;

use Bitrix\Main\Engine\Controller;

class TestController extends Controller
{
    public function init()
    {
        parent::init();
    }

    public function showAction()
    {
        return ['hellow'];
    }
}
