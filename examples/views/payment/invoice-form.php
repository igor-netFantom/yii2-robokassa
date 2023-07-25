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

/** @var Yii2Robokassa $robokassa */
$robokassa = Yii::$app->get('robokassa');

echo Html::encode("Сформирован счет №$invoice->id на сумму $invoice->sum руб. и ждет оплаты");

$form = ActiveForm::begin([
    'id' => 'pay-form',
    'method' => 'POST',
    'action' => $robokassa->paymentUrl,
]);
echo $robokassa->getHiddenInputsHtml($invoice->getInvoiceOptions());
echo Html::submitButton('оплатить', [
    'onClick' => PopupIframeWidget::SHOW_ROBOKASSA_POPUP_IFRAME_ACTION,
    'class' => 'btn btn-primary btn-lg',
]);
ActiveForm::end();