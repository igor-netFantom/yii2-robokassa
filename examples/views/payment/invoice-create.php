<?php

declare(strict_types=1);

use models\Invoice;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $invoice Invoice */

?>
<?php
$form = ActiveForm::begin([
    'id' => 'pay-form'
]);
?>
<?= $form->field($invoice, 'sum')->textInput() ?>
<?= Html::submitButton('Pay', ['class' => 'btn btn-success']) ?>
<?php ActiveForm::end(); ?>