yii2-robokassa
==============
[![Latest Stable Version](http://poser.pugx.org/netfantom/yii2-robokassa/v)](https://packagist.org/packages/netfantom/yii2-robokassa)
[![Total Downloads](http://poser.pugx.org/netfantom/yii2-robokassa/downloads)](https://packagist.org/packages/netfantom/yii2-robokassa)
[![License](http://poser.pugx.org/netfantom/yii2-robokassa/license)](https://packagist.org/packages/netfantom/yii2-robokassa)
[![PHP Version Require](http://poser.pugx.org/netfantom/yii2-robokassa/require/php)](https://packagist.org/packages/netfantom/yii2-robokassa)
[![codecov](https://codecov.io/gh/igor-netFantom/yii2-robokassa/branch/main/graph/badge.svg?token=61PMP5UL0Z)](https://codecov.io/gh/igor-netFantom/yii2-robokassa)
[![type-coverage](https://shepherd.dev/github/igor-netFantom/yii2-robokassa/coverage.svg)](https://shepherd.dev/github/igor-netfantom/yii2-robokassa)
[![psalm-level](https://shepherd.dev/github/igor-netFantom/yii2-robokassa/level.svg)](https://shepherd.dev/github/igor-netfantom/yii2-robokassa)

Данный компонент является оберткой вокруг [`netFantom/robokassa-api`](https://github.com/igor-netFantom/robokassa-api)
выполненным в виде компонента Yii 2 framework
и предназначен для взаимодействия с Робокассой.

Для работы требуется `PHP 8.1+`

Компонент `netFantom/yii2-robokassa` расширяет
возможности [`netFantom/robokassa-api`](https://github.com/igor-netFantom/robokassa-api),
добавляя удобные методы и виджеты.

Для настройки компонента, формирования запросов и обработки ответов используются
объекты [`netFantom/robokassa-api`](https://github.com/igor-netFantom/robokassa-api/tree/main/src/Options)

## Установка с помощью Composer

~~~
composer require igor-netfantom/yii2-robokassa:@dev
~~~

## Подключение компонента

Объектом:

```php
[
    // ...
    'components' => [
        'robokassa' => [
            'class' => 'netFantom\Yii2Robokassa\Yii2Robokassa',
            'robokassaApi' => new \netFantom\RobokassaApi\RobokassaApi(
                merchantLogin: 'robo-demo',
                password1: 'password_1',
                password2: 'password_2',
                isTest: !YII_ENV_PROD,
            ),
        ],
        // ...
    ],
];
```

...или массивом:

```php
[
    // ...
    'components' => [
        'robokassa' => [
            'class' => 'netFantom\Yii2Robokassa\Yii2Robokassa',
            'merchantLogin' => 'robo-demo',
            'password1' => 'password1',
            'password2' => 'password2',
            'isTest' => !YII_ENV_PROD,
        ],
        // ...
    ],
];
```

## Объект для формирования оплаты счета

```php
$invoiceOptions = new \netFantom\RobokassaApi\Options\InvoiceOptions(
    outSum: 999.99,
    invId: 1,
    description: 'Description',
    receipt: new \netFantom\RobokassaApi\Options\Receipt(
        items: [
            new \netFantom\RobokassaApi\Options\Item(
                name: "Название товара 1",
                quantity: 1,
                sum: 100,
                tax: \netFantom\RobokassaApi\Options\Tax::vat10,
                payment_method: \netFantom\RobokassaApi\Options\PaymentMethod::full_payment,
                payment_object: \netFantom\RobokassaApi\Options\PaymentObject::commodity,
            ),
            new \netFantom\RobokassaApi\Options\Item(
                name: "Название товара 2",
                quantity: 3,
                sum: 450,
                tax: \netFantom\RobokassaApi\Options\Tax::vat10,
                payment_method: \netFantom\RobokassaApi\Options\PaymentMethod::full_payment,
                payment_object: \netFantom\RobokassaApi\Options\PaymentObject::service,
                cost: 150,
                nomenclature_code: '04620034587217',
            ),
        ],
        sno: \netFantom\RobokassaApi\Options\Sno::osn
    ),
    expirationDate: (new \DateTime('2030-01-01 10:20:30', new \DateTimeZone('+3'))),
    email: 'user@email.com',
    outSumCurrency: \netFantom\RobokassaApi\Options\OutSumCurrency::USD,
    userIP: '127.0.0.1', 
    incCurrLabel: null,
    userParameters: [
        'user_id'=>'123',
        'parameter2'=>'parameter2_value',
        // ...
    ],
    encoding: 'utf-8',
    culture: \netFantom\RobokassaApi\Options\Culture::ru,
)
```

## Объект для получения и обработки ответа Робокассы

```php
/** @var \netFantom\RobokassaApi\Options\ResultOptions $resultOptions  */
$resultOptions=\netFantom\Yii2Robokassa\Yii2Robokassa::getResultOptionsFromRequest(Yii::$app->request);

/** @var \netFantom\Yii2Robokassa\Yii2Robokassa $yii2Robokassa */
$yii2Robokassa=Yii::$app->robokassa;
if(!$yii2Robokassa->checkSignature($resultOptions)) {
    throw new \yii\web\HttpException(400,'Bad signature');
}

$resultOptions->invId; // номер счета
$resultOptions->outSum; // сумма оплаты
$resultOptions->signatureValue; // подпись
$resultOptions->userParameters; // дополнительные пользовательские параметры
// [
//     'user_id'=>'123',
//     'parameter2' => 'parameter2_value',
//     '...' => ...,
// ]
```

## Методы

### Методы для оплаты и отправки других запросов

Получение URL для оплаты счета с указанными параметрами

```php
public function getPaymentUrl(InvoiceOptions $invoiceOptions): string
```

Переадресация на страницу оплаты счета

```php
public function redirectToPaymentUrl(InvoiceOptions $invoiceOptions, bool $setReturnUrl = true): Response
```

Получает параметры платежа для передачи в Робокассу (для формирования формы оплаты с методом передачи POST запросом)

```php
public function paymentParameters(InvoiceOptions $invoiceOptions): array
//[
//    'MerchantLogin' => ...,
//    'OutSum' => ...,
//    'Description' => ...,
//    'SignatureValue' => ...,
//    'IncCurrLabel' => ...,
//    'InvId' => ...,
//    'Culture' => ...,
//    'Encoding' => ...,
//    'Email' => ...,
//    'ExpirationDate' => ...,
//    'OutSumCurrency' => ...,
//    'UserIp' => ...,
//    'Receipt' => ...,
//    'IsTest' => ...,
//    'shp_...' => ...,
//    'shp_...' => ...,
//    'shp_...' => ...,
//    // ...
//]
```

Преобразует параметры платежа `InvoiceOptions` в скрытые поля формы `Html::hiddenInput()` для отправки пользователя на
оплату `POST` запросом

```php
public function getHiddenInputsHtml(InvoiceOptions $invoiceOptions): string
```

Отправка СМС

```php
/**
* @param int $phone Номер телефона в международном формате без символа «+». Например, 8999*******.
* @param string $message строка в кодировке UTF-8 длиной до 128 символов, содержащая текст отправляемого SMS.
* @return \yii\httpclient\Response Можно использовать для проверки ответа сервера
*/
public function sendSms(int $phone, string $message): \yii\httpclient\Response
```

### Методы проверки ответа Робокассы

Получение параметров результата ResultOptions от Робокассы
из GET или POST параметров HTTP запроса \yii\web\Request

```php
public static function getResultOptionsFromRequest(\yii\web\Request $request): ResultOptions
```

Проверка корректности подписи параметров результата ResultOptions от Робокассы

```php
public function checkSignature(ResultOptions $resultOptions): bool
```

## Пример использования компонента

### Пример моделей счета

```php
class InvoiceStatus
{
    public const STATUS_CREATED = 1;
    public const STATUS_PAYED = 2;
    public const STATUS_FAILED = 3;
}
```

```php
class PaymentSystem
{
    public const SYSTEM_ROBOKASSA = 1;
}
```

```php
class Invoice extends \yii\db\ActiveRecord
{
    public int $id;
    public string $sum;
    public int $status_id = \models\InvoiceStatus::STATUS_CREATED;
    public int $payment_system_id;
    public int $user_id;

    public function getInvoiceOptions(): \netFantom\RobokassaApi\Options\InvoiceOptions
    {
        return new \netFantom\RobokassaApi\Options\InvoiceOptions(
            outSum: $this->sum,
            invId: $this->id,
            description: 'Description',
            receipt: new \netFantom\RobokassaApi\Options\Receipt(
                items: [
                    new \netFantom\RobokassaApi\Options\Item(
                        name: "Название товара 1",
                        quantity: 1,
                        sum: 100,
                        tax: \netFantom\RobokassaApi\Options\Tax::vat10,
                        payment_method: \netFantom\RobokassaApi\Options\PaymentMethod::full_payment,
                        payment_object: \netFantom\RobokassaApi\Options\PaymentObject::commodity,
                    ),
                    new \netFantom\RobokassaApi\Options\Item(
                        name: "Название товара 2",
                        quantity: 3,
                        sum: 450,
                        tax: \netFantom\RobokassaApi\Options\Tax::vat10,
                        payment_method: \netFantom\RobokassaApi\Options\PaymentMethod::full_payment,
                        payment_object: \netFantom\RobokassaApi\Options\PaymentObject::service,
                        cost: 150,
                        nomenclature_code: '04620034587217',
                    ),
                ],
                sno: \netFantom\RobokassaApi\Options\Sno::osn
            ),
            culture: \netFantom\RobokassaApi\Options\Culture::ru,
        );
    }
    //...
}
```

### [Примеры действий контроллера](https://github.com/igor-netFantom/yii2-robokassa/blob/main/examples/controllers/PaymentController.php) для обработки запросов Робокассы

```php
class PaymentController extends \yii\web\Controller
{
    /**
     * @link https://docs.robokassa.ru/pay-interface/
     */
    public function actionFail(): \yii\web\Response
    {
        $resultOptions = \netFantom\Yii2Robokassa\Yii2Robokassa::getResultOptionsFromRequest(Yii::$app->request);
        $invoice = $this->loadInvoice($resultOptions->invId);

        if ($invoice->status_id === \models\InvoiceStatus::STATUS_CREATED) {
            $invoice->updateAttributes(['status' => \models\InvoiceStatus::STATUS_FAILED]);
        }
        return $this->goBack();
    }

    /**
     * @link https://docs.robokassa.ru/pay-interface/
     */
    public function actionResult(): string
    {
        /** @var \netFantom\Yii2Robokassa\Yii2Robokassa $Yii2Robokassa */
        $Yii2Robokassa = Yii::$app->get('robokassa');

        $resultOptions = \netFantom\Yii2Robokassa\Yii2Robokassa::getResultOptionsFromRequest(Yii::$app->request);
        if (!$Yii2Robokassa->checkSignature($resultOptions)) {
            throw new \yii\web\BadRequestHttpException();
        }

        if (!$this->loadInvoice($resultOptions->invId)->updateAttributes([
            'status' => \models\InvoiceStatus::STATUS_PAYED
        ])) {
            throw new \yii\web\BadRequestHttpException();
        }

        return $resultOptions->formatOkAnswer();
    }

    /**
     * @link https://docs.robokassa.ru/pay-interface/
     */
    public function actionSuccess(): \yii\web\Response|string
    {
        $resultOptions = \netFantom\Yii2Robokassa\Yii2Robokassa::getResultOptionsFromRequest(Yii::$app->request);
        $invoice = $this->loadInvoice($resultOptions->invId);

        return $this->render("success", compact('invoice'));
    }

    protected function loadInvoice(int $id): \models\Invoice
    {
        $invoice = \models\Invoice::find()
            ->andWhere(['id' => $id])
            ->andWhere(['payment_system_id' => \models\PaymentSystem::SYSTEM_ROBOKASSA])
            ->one();
        if ($invoice === null) {
            throw new \yii\web\BadRequestHttpException('Подходящий по условиям счёт не найден');
        }
        return $invoice;
    }
}
```

### [Примеры действий](https://github.com/igor-netFantom/yii2-robokassa/blob/main/examples/controllers/PaymentController.php) для создания счета и отправки пользователя на оплату

#### ВАРИАНТ: Загрузка Popup виджета оплаты AJAX запросом

```php
public function actionInvoiceAjax(): string
{
    $invoice = new \models\Invoice();
    $invoice->payment_system_id = \models\PaymentSystem::SYSTEM_ROBOKASSA;
    $invoice->status_id = \models\InvoiceStatus::STATUS_CREATED;
    $invoice->user_id = Yii::$app->user->id;

    if (Yii::$app->request->isAjax && $invoice->load(Yii::$app->request->post()) && $invoice->save()) {
        return $this->renderAjax('invoice-ajax-response', compact('invoice'));
    }

    \netFantom\Yii2Robokassa\Assets\PopupIframeAsset::register($this->view);
    return $this->render('invoice-ajax', [
        'invoice' => $invoice,
    ]);
}
```

Пример представления
[`invoice-ajax`](https://github.com/igor-netFantom/yii2-robokassa/blob/main/examples/views/payment/invoice-ajax.php)
для действия `actionInvoiceAjax`

```php
$url = \yii\helpers\Url::current();

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

$form = \yii\widgets\ActiveForm::begin([
    'id' => 'pay-form',
    'enableClientValidation' => false,
]); 
echo $form->field($invoice, 'sum')->textInput();
echo Html::submitButton('Pay', ['class' => 'btn btn-success']) 
\yii\widgets\ActiveForm::end(); 
```

Пример представления
[`invoice-ajax-response`](https://github.com/igor-netFantom/yii2-robokassa/blob/main/examples/views/payment/invoice-ajax-response.php)
для действия `actionInvoiceAjax`

```php
/** @var Yii2Robokassa $robokassa */
$robokassa = Yii::$app->get('robokassa');

\netFantom\Yii2Robokassa\Widgets\PopupIframeWidget::widget([
    'yii2Robokassa' => $robokassa,
    'invoiceOptions' => $invoice->getInvoiceOptions(),
    'registerAsset' => false,
]);

echo \yii\helpers\Html::encode("Сформирован счет №$invoice->id на сумму $invoice->sum руб. и ждет оплаты");
echo \yii\helpers\Html::button('оплатить', [
    'onClick' => \netFantom\Yii2Robokassa\Widgets\PopupIframeWidget::SHOW_ROBOKASSA_POPUP_IFRAME_ACTION,
    'class' => 'btn btn-primary',
]);
```

#### ВАРИАНТ: Переход на оплату формой с POST запросом

```php
public function actionInvoiceForm(int $id = null): string
{
    if (isset($id)) {
        $invoice = $this->loadInvoice($id);
        if ($invoice->user_id !== Yii::$app->user->id || $invoice->status_id !== \models\InvoiceStatus::STATUS_CREATED) {
            throw new \yii\web\BadRequestHttpException('Подходящий по условиям счёт не найден');
        }
        return $this->render('invoice-form', compact('invoice'));
    }

    $invoice = new \models\Invoice();
    $invoice->payment_system_id = \models\PaymentSystem::SYSTEM_ROBOKASSA;
    $invoice->status_id = \models\InvoiceStatus::STATUS_CREATED;
    $invoice->user_id = Yii::$app->user->id;

    if ($invoice->load(Yii::$app->request->post()) && $invoice->save()) {
        $this->redirect(['payment/invoice-form', 'id' => $invoice->id]);
    }

    return $this->render('invoice-create', [
        'invoice' => $invoice,
    ]);
}
```

Пример представления
[`invoice-form`](https://github.com/igor-netFantom/yii2-robokassa/blob/main/examples/views/payment/invoice-form.php)
для действия `actionInvoiceForm`

```php
echo \yii\helpers\Html::encode("Сформирован счет №$invoice->id на сумму $invoice->sum руб. и ждет оплаты");

/** @var \netFantom\Yii2Robokassa\Yii2Robokassa $yii2Robokassa */
$yii2Robokassa = Yii::$app->get('robokassa');

$form = \yii\widgets\ActiveForm::begin([
    'id' => 'pay-form',
    'method' => 'POST',
    'action' => $yii2Robokassa->paymentUrl,
]);
echo $yii2Robokassa->getHiddenInputsHtml($invoice->getInvoiceOptions());
echo \yii\helpers\Html::submitButton('оплатить', [
    'onClick' => \netFantom\Yii2Robokassa\Widgets\PopupIframeWidget::SHOW_ROBOKASSA_POPUP_IFRAME_ACTION,
    'class' => 'btn btn-primary btn-lg',
]);
\yii\widgets\ActiveForm::end();
```

#### ВАРИАНТ: Формирование Popup виджета

```php
public function actionInvoicePopup(int $id = null): string
{
    if (isset($id)) {
        $invoice = $this->loadInvoice($id);
        if ($invoice->user_id !== Yii::$app->user->id || $invoice->status_id !== \models\InvoiceStatus::STATUS_CREATED) {
            throw new \yii\web\BadRequestHttpException('Подходящий по условиям счёт не найден');
        }
        return $this->render('invoice-popup', compact('invoice'));
    }

    $invoice = new \models\Invoice();
    $invoice->payment_system_id = \models\PaymentSystem::SYSTEM_ROBOKASSA;
    $invoice->status_id = \models\InvoiceStatus::STATUS_CREATED;
    $invoice->user_id = Yii::$app->user->id;

    if ($invoice->load(Yii::$app->request->post()) && $invoice->save()) {
        $this->redirect(['payment/invoice-popup', 'id' => $invoice->id]);
    }

    return $this->render('invoice-create', [
        'invoice' => $invoice,
    ]);
}
```

Пример представления
[`invoice-popup`](https://github.com/igor-netFantom/yii2-robokassa/blob/main/examples/views/payment/invoice-popup.php)
для действия `actionInvoicePopup`

```php
\netFantom\Yii2Robokassa\Widgets\PopupIframeWidget::widget([
    'yii2Robokassa' => $robokassa,
    'invoiceOptions' => $invoice->getInvoiceOptions(),
    'showOnLoad' => true,
]);
echo \yii\helpers\Html::encode("Сформирован счет №$invoice->id на сумму $invoice->sum руб. и ждет оплаты");
echo \yii\helpers\Html::button('оплатить', [
    'onClick' => \netFantom\Yii2Robokassa\Widgets\PopupIframeWidget::SHOW_ROBOKASSA_POPUP_IFRAME_ACTION,
    'class' => 'btn btn-primary btn-lg',
]);
```

Пример представления
[`invoice-create`](https://github.com/igor-netFantom/yii2-robokassa/blob/main/examples/views/payment/invoice-create.php)
для действий `actionInvoicePopup` и `actionInvoiceForm`

```php
$form = \yii\widgets\ActiveForm::begin(); 
echo $form->field($invoice, 'sum')->textInput();
echo \yii\helpers\Html::submitButton('Пополнить баланс', ['class' => 'btn btn-success']);
\app\widgets\activeForm\ActiveForm::end(); 
```