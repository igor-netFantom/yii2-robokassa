<?php

declare(strict_types=1);

namespace netFantom\Yii2Robokassa\Widgets;

use JsonException;
use netFantom\RobokassaApi\Options\InvoiceOptions;
use netFantom\Yii2Robokassa\Assets\PopupIframeAsset;
use netFantom\Yii2Robokassa\Yii2Robokassa;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\helpers\Json;
use yii\web\View;

class PopupIframeWidget extends Widget
{
    public const ROBOKASSA_OPTIONS_VAR = 'Yii2RobokassaOptions';
    public const SHOW_ROBOKASSA_POPUP_IFRAME_ACTION = 'Robokassa.StartPayment(' . self::ROBOKASSA_OPTIONS_VAR . ')';
    /**
     * @var Yii2Robokassa
     * @psalm-suppress PropertyNotSetInConstructor
     * Yii could set this property from parent::__construct()
     */
    public Yii2Robokassa $yii2Robokassa;
    /**
     * @var InvoiceOptions
     * @psalm-suppress PropertyNotSetInConstructor
     * Yii could set this property from parent::__construct()
     */
    public InvoiceOptions $invoiceOptions;
    public bool $showOnLoad = true;
    public bool $registerAsset = true;

    /**
     * @inheritdoc
     * @throws InvalidConfigException|JsonException
     */
    public function init(): void
    {
        parent::init();
        /**
         * @psalm-suppress RedundantPropertyInitializationCheck
         * Yii call init() method from parent::__construct() and yii2Robokassa could not be set yet
         */
        if (!isset($this->yii2Robokassa)) {
            throw new InvalidConfigException('yii2Robokassa must be set');
        }
        /**
         * @psalm-suppress RedundantPropertyInitializationCheck
         * Yii call init() method from parent::__construct() and invoiceOptions could not be set yet
         */
        if (!isset($this->invoiceOptions)) {
            throw new InvalidConfigException('invoiceOptions must be set');
        }
        $this->registerClientScript();
    }

    /**
     * @throws JsonException
     */
    public function registerClientScript(): void
    {
        $view = $this->getView();

        if ($this->registerAsset) {
            PopupIframeAsset::register($view);
        }

        $encodedInvoiceOptions = Json::htmlEncode($this->yii2Robokassa->paymentParameters($this->invoiceOptions));
        $view->registerJs(
            "var " . self::ROBOKASSA_OPTIONS_VAR . "=$encodedInvoiceOptions;",
            View::POS_HEAD,
            'yii2-robokassa-popup-iframe-options'
        );
        if ($this->showOnLoad) {
            $view->registerJs(
                self::SHOW_ROBOKASSA_POPUP_IFRAME_ACTION,
                View::POS_READY,
                'yii2-robokassa-iframe-start-payment'
            );
        }
    }
}
