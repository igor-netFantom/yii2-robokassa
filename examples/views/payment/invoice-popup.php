<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

use models\Invoice;
use netFantom\Yii2Robokassa\Widgets\PopupIframeWidget;
use netFantom\Yii2Robokassa\Yii2Robokassa;
use yii\helpers\Html;
use yii\web\View;

/* @var $this View */
/* @var $invoice Invoice */

/** @var Yii2Robokassa $robokassa */
$robokassa = Yii::$app->get('robokassa');

PopupIframeWidget::widget([
    'yii2Robokassa' => $robokassa,
    'invoiceOptions' => $invoice->getInvoiceOptions(),
    'showOnLoad' => true,
]);

$this->title = 'Счет №' . $invoice->id;
?>
<div class="px-4 py-5 my-5 text-center">
    <div class="lead mb-4">
        Сформирован счет №<?= Html::encode($invoice->id) ?>
        на сумму <?= Html::encode($invoice->sum) ?> руб.
        и ждет оплаты
    </div>
    <div>
        <?= Html::button('оплатить', [
            'onClick' => PopupIframeWidget::SHOW_ROBOKASSA_POPUP_IFRAME_ACTION,
            'class' => 'btn btn-primary btn-lg',
        ]) ?>
    </div>
</div>
