<?php

declare(strict_types=1);

namespace tests;

use netFantom\RobokassaApi\Options\InvoiceOptions;
use netFantom\Yii2Robokassa\Widgets\PopupIframeWidget;
use Yii;

/**
 * @group robokassa
 * @group Yii2Robokassa
 */
class PopupWidgetTest extends TestCase
{
    public function setUp(): void
    {
        $this->mockWebApplication([
            'components' => [
                'robokassa' => [
                    'class' => '\netFantom\Yii2Robokassa\Yii2Robokassa',
                    'merchantLogin' => 'robo-demo',
                    'password1' => 'Пароль#1',
                    'password2' => 'Пароль#2',
                    'isTest' => true,
                ],
            ]
        ]);
    }

    public function testEmptyInvoiceOptions(): void
    {
        $this->expectExceptionMessage('invoiceOptions must be set');
        PopupIframeWidget::widget([
            'yii2Robokassa' => Yii::$app->robokassa,
        ]);
    }

    public function testEmptyYii2Robokassa(): void
    {
        $this->expectExceptionMessage('yii2Robokassa must be set');
        PopupIframeWidget::widget([
            'invoiceOptions' => new InvoiceOptions(
                outSum: 100,
                invId: 1,
                description: 'Description',
            ),
        ]);
    }

    public function testSuccess(): void
    {
        PopupIframeWidget::widget([
            'yii2Robokassa' => Yii::$app->robokassa,
            'invoiceOptions' => new InvoiceOptions(
                outSum: 100,
                invId: 1,
                description: 'Description',
            ),
        ]);
        $this->assertTrue(true);
    }
}
