<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Регистрация");
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php $APPLICATION->IncludeComponent(
                    'shop:register.form',
                    '',
                    [
                        'BACKURL' => '/',
                    ],
                    false
                ); ?>
            </div>
        </div>
    </div>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>