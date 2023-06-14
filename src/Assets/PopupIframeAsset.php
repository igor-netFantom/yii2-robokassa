<?php

declare(strict_types=1);

namespace netFantom\Yii2Robokassa\Assets;

use yii\web\AssetBundle;

class PopupIframeAsset extends AssetBundle
{
    public $js = [
        'https://auth.robokassa.ru/Merchant/bundle/robokassa_iframe.js',
    ];
}