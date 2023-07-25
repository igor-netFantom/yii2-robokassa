<?php

declare(strict_types=1);

namespace models;

use netFantom\RobokassaApi\Options\InvoiceOptions;
use netFantom\RobokassaApi\Params\{Culture, Receipt};
use netFantom\RobokassaApi\Params\Item\{PaymentMethod, PaymentObject};
use netFantom\RobokassaApi\Params\Receipt\{Item, Sno, Tax};
use yii\db\ActiveRecord;

class Invoice extends ActiveRecord
{
    public int $id;
    public string $sum;
    public int $status_id = InvoiceStatus::STATUS_CREATED;
    public int $payment_system_id;
    public int $user_id;

    public function getInvoiceOptions(): InvoiceOptions
    {
        return new InvoiceOptions(
            outSum: $this->sum,
            invId: $this->id,
            description: 'Description',
            receipt: new Receipt(
                items: [
                    new Item(
                        name: "Название товара 1",
                        quantity: 1,
                        sum: 100,
                        tax: Tax::vat10,
                        payment_method: PaymentMethod::full_payment,
                        payment_object: PaymentObject::commodity,
                    ),
                    new Item(
                        name: "Название товара 2",
                        quantity: 3,
                        sum: 450,
                        tax: Tax::vat10,
                        payment_method: PaymentMethod::full_payment,
                        payment_object: PaymentObject::service,
                        cost: 150,
                        nomenclature_code: '04620034587217',
                    ),
                ],
                sno: Sno::osn
            ),
            culture: Culture::ru,
        );
    }
}