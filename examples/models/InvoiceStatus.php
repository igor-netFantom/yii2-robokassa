<?php

declare(strict_types=1);

namespace models;

use yii\db\ActiveRecord;

class InvoiceStatus extends ActiveRecord
{
    public const STATUS_CREATED = 1;
    public const STATUS_PAYED = 2;
    public const STATUS_FAILED = 3;
}