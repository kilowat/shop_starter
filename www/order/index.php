<?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->SetTitle('Оформление заказа');

$APPLICATION->IncludeComponent(
    'bitrix:sale.order.ajax',
    'bootstrap_v4',
    [
        'PATH_TO_BASKET' => '/cart/',
        'PATH_TO_PERSONAL' => '/personal/',
        'PATH_TO_PAYMENT' => '/order/payment.php',
        'PATH_TO_AUTH' => '/register/',
        'ALLOW_AUTO_REGISTER' => 'Y',
        'SEND_NEW_USER_NOTIFY' => 'Y',
        'DELIVERY_NO_AJAX' => 'N',
        'DELIVERY_NO_SESSION' => 'Y',
        'DELIVERY_TO_PAYSYSTEM' => 'd2p',
        'TEMPLATE_LOCATION' => 'popup',
        'USE_PREPAYMENT' => 'N',
        'SET_TITLE' => 'N',
        'SHOW_COUPONS' => 'Y',
    ],
    false
);

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');