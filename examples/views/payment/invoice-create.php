<?php

declare(strict_types=1);

use models\Invoice;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $invoice Invoice */

$form = ActiveForm::begin();
echo $form->field($invoice, 'sum')->textInput();
echo Html::submitButton('Пополнить баланс', ['class' => 'btn btn-success']);
ActiveForm::end();