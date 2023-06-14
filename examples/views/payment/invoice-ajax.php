<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

use models\Invoice;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $invoice Invoice */

$url = Url::current();
/** @noinspection JSUnresolvedFunction */
/** @noinspection JSUnresolvedVariable */
$this->registerJs(
    <<<JS
    $('form').on('beforeSubmit', function(){
       var data = $(this).serialize();
        $.ajax({
            url: '$url',
            type: 'POST',
            data: data,
            success: function(res){
                let responsePayForm=$(res).find('#pay-form');
                if(responsePayForm.length>0) {
                    $('#pay-form').html(responsePayForm);
                } else {
                    $('#pay-form').append(res);
                }
            },
            error: function(){
                alert('Error!');
            }
        });
        return false;
    });
JS
);
?>
<?php
$form = ActiveForm::begin([
    'id' => 'pay-form',
    'enableClientValidation' => false,
]); ?>
<?= $form->field($invoice, 'sum')->textInput() ?>
<?= Html::submitButton('Pay', ['class' => 'btn btn-success']) ?>
<?php ActiveForm::end(); ?>