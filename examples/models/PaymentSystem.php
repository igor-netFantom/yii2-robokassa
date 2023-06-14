<?php

declare(strict_types=1);

namespace models;

use yii\db\ActiveRecord;

class PaymentSystem extends ActiveRecord
{
    public const SYSTEM_ROBOKASSA = 1;
}