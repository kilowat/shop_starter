<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

if ($arParams['SET_TITLE'] == 'Y') {
    $APPLICATION->SetTitle(Loc::getMessage('SOA_ORDER_COMPLETE'));
}
?>

<?php if (!empty($arResult['ORDER'])): ?>
    <div class="checkout-confirm">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h1 class="h4 mb-2">Спасибо, заказ оформлен</h1>
                <p class="text-muted mb-0">
                    <?= Loc::getMessage('SOA_ORDER_SUC', [
                        '#ORDER_DATE#' => $arResult['ORDER']['DATE_INSERT']->toUserTime()->format('d.m.Y H:i'),
                        '#ORDER_ID#' => htmlspecialcharsbx($arResult['ORDER']['ACCOUNT_NUMBER']),
                    ]) ?>
                </p>

                <?php if (!empty($arResult['ORDER']['PAYMENT_ID'])): ?>
                    <p class="text-muted mb-0 mt-2">
                        <?= Loc::getMessage('SOA_PAYMENT_SUC', [
                            '#PAYMENT_ID#' => htmlspecialcharsbx(
                                $arResult['PAYMENT'][$arResult['ORDER']['PAYMENT_ID']]['ACCOUNT_NUMBER']
                            ),
                        ]) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($arResult['ORDER']['IS_ALLOW_PAY'] === 'Y'): ?>
            <?php if (!empty($arResult['PAYMENT'])): ?>
                <?php foreach ($arResult['PAYMENT'] as $payment): ?>
                    <?php if ($payment['PAID'] === 'Y') { continue; } ?>

                    <?php if (
                        !empty($arResult['PAY_SYSTEM_LIST'])
                        && array_key_exists($payment['PAY_SYSTEM_ID'], $arResult['PAY_SYSTEM_LIST'])
                    ): ?>
                        <?php
                        $arPaySystem = $arResult['PAY_SYSTEM_LIST_BY_PAYMENT_ID'][$payment['ID']];
                        ?>
                        <?php if (empty($arPaySystem['ERROR'])): ?>
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-body">
                                    <h2 class="h6 mb-3"><?= Loc::getMessage('SOA_PAY') ?></h2>

                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <strong><?= $arPaySystem['NAME'] ?></strong>
                                        <?= CFile::ShowImage(
                                            $arPaySystem['LOGOTIP'],
                                            100,
                                            100,
                                            'border=0 style="width:100px"',
                                            '',
                                            false
                                        ) ?>
                                    </div>

                                    <?php if (
                                        $arPaySystem['ACTION_FILE'] !== ''
                                        && $arPaySystem['NEW_WINDOW'] === 'Y'
                                        && $arPaySystem['IS_CASH'] !== 'Y'
                                    ): ?>
                                        <?php
                                        $orderAccountNumber = urlencode(urlencode($arResult['ORDER']['ACCOUNT_NUMBER']));
                                        $paymentAccountNumber = $payment['ACCOUNT_NUMBER'];
                                        $paymentUrl = $arParams['PATH_TO_PAYMENT']
                                            . '?ORDER_ID=' . $orderAccountNumber
                                            . '&PAYMENT_ID=' . $paymentAccountNumber;
                                        ?>
                                        <a class="btn btn-primary" href="<?= htmlspecialcharsbx($paymentUrl) ?>" target="_blank" rel="noopener">
                                            Перейти к оплате
                                        </a>

                                        <?php if (CSalePdf::isPdfAvailable() && $arPaySystem['IS_AFFORD_PDF']): ?>
                                            <a class="btn btn-link"
                                               href="<?= htmlspecialcharsbx($arParams['PATH_TO_PAYMENT']) ?>?ORDER_ID=<?= $orderAccountNumber ?>&pdf=1&DOWNLOAD=Y">
                                                Скачать квитанцию
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?= $arPaySystem['BUFFERED_OUTPUT'] ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger" role="alert">
                                <?= Loc::getMessage('SOA_ORDER_PS_ERROR') ?>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-danger" role="alert">
                            <?= Loc::getMessage('SOA_ORDER_PS_ERROR') ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-danger" role="alert">
                <?= $arParams['MESS_PAY_SYSTEM_PAYABLE_ERROR'] ?>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="/personal/">Мои заказы</a>
            <a class="btn btn-outline-secondary" href="/catalog/">Продолжить покупки</a>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-danger" role="alert">
        <strong><?= Loc::getMessage('SOA_ERROR_ORDER') ?></strong><br>
        <?= Loc::getMessage('SOA_ERROR_ORDER_LOST', [
            '#ORDER_ID#' => htmlspecialcharsbx($arResult['ACCOUNT_NUMBER']),
        ]) ?><br>
        <?= Loc::getMessage('SOA_ERROR_ORDER_LOST1') ?>
    </div>
<?php endif; ?>