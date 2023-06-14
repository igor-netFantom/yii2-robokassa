<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

use models\Invoice;
use netFantom\Yii2Robokassa\Widgets\PopupIframeWidget;
use netFantom\Yii2Robokassa\Yii2Robokassa;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $invoice Invoice */

/** @var Yii2Robokassa $yii2Robokassa */
$yii2Robokassa = Yii::$app->get('robokassa');
?>

<div class="px-4 py-5 my-5 text-center">
    <div class="lead mb-4">
        Сформирован счет №<?= Html::encode($invoice->id) ?>
        на сумму <?= Html::encode($invoice->sum) ?> руб.
        и ждет оплаты
    </div>
    <div>
        <?php
        $form = ActiveForm::begin([
            'id' => 'pay-form',
            'method' => 'POST',
            'action' => $yii2Robokassa->paymentUrl,
        ]);
        echo $yii2Robokassa->getHiddenInputsHtml($invoice->getInvoiceOptions());
        echo Html::submitButton('оплатить', [
            'onClick' => PopupIframeWidget::SHOW_ROBOKASSA_POPUP_IFRAME_ACTION,
            'class' => 'btn btn-primary btn-lg',
        ]);
        ActiveForm::end();
        ?>
    </div>
</div>
