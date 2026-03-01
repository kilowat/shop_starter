<?
use Lib\AjaxHtmlResponse;

if ($_REQUEST['ajax_request'] === 'Y') {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

    AjaxHtmlResponse::sendResponse(
        '/catalog/_products_list.php',
    );

}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Title");

include('_products_list.php');

?><? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>