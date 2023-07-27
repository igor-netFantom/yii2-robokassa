yii2-robokassa
==============
[![Latest Stable Version](http://poser.pugx.org/netfantom/yii2-robokassa/v)](https://packagist.org/packages/netfantom/yii2-robokassa)
[![Total Downloads](http://poser.pugx.org/netfantom/yii2-robokassa/downloads)](https://packagist.org/packages/netfantom/yii2-robokassa)
[![License](http://poser.pugx.org/netfantom/yii2-robokassa/license)](https://packagist.org/packages/netfantom/yii2-robokassa)
[![PHP Version Require](http://poser.pugx.org/netfantom/yii2-robokassa/require/php)](https://packagist.org/packages/netfantom/yii2-robokassa)
[![codecov](https://codecov.io/gh/igor-netFantom/yii2-robokassa/branch/main/graph/badge.svg?token=61PMP5UL0Z)](https://codecov.io/gh/igor-netFantom/yii2-robokassa)
[![type-coverage](https://shepherd.dev/github/igor-netFantom/yii2-robokassa/coverage.svg)](https://shepherd.dev/github/igor-netfantom/yii2-robokassa)
[![psalm-level](https://shepherd.dev/github/igor-netFantom/yii2-robokassa/level.svg)](https://shepherd.dev/github/igor-netfantom/yii2-robokassa)

Данный компонент предназначен для работы с Робокассой:

- является оберткой вокруг [`netFantom/robokassa-api`](https://github.com/igor-netFantom/robokassa-api)
  и полностью исполняет его интерфейс `netFantom\robokassa-api\RobokassaApiInterface`<br>
  ( см. https://github.com/igor-netFantom/robokassa-api )
- выполнен в виде компонента `Yii 2 framework` и расширяет возможности,
  добавляя удобные методы и виджеты

Для работы требуется `PHP 8.1+`

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
                psr18Client: new \Http\Discovery\Psr18Client(), // необязательно
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
            'psr18Client' =>'Http\Discovery\Psr18Client', // необязательно
        ],
        // ...
    ],
];
```

## Методы

- [Переадресация на страницу оплаты счета](#переадресация-на-страницу-оплаты-счета)
- [Преобразование параметров платежа в поля формы](#преобразование-параметров-платежа-в-поля-формы)
- [Получение результата оплаты счета от Робокассы из HTTP запроса Yii](#получение-результата-оплаты-счета-от-робокассы-из-http-запроса-yii)
- [Методы модуля `netFantom/robokassa-api`](#методы-модуля-netfantomrobokassa-api)
  - [🔗 Получение параметров платежа для передачи в Робокассу](https://github.com/igor-netFantom/robokassa-api#получение-параметров-платежа-для-передачи-в-робокассу)
  - [🔗 Получение параметров платежа в формате `JSON строки`](https://github.com/igor-netFantom/robokassa-api#получение-параметров-платежа-в-формате-json-строки)
  - [🔗 Получение URL для оплаты счета с указанными параметрами](https://github.com/igor-netFantom/robokassa-api#получение-url-для-оплаты-счета-с-указанными-параметрами)
  - [🔗 Отправка второго чека](https://github.com/igor-netFantom/robokassa-api#отправка-второго-чека)
  - [🔗 Получение статуса чека](https://github.com/igor-netFantom/robokassa-api#получение-статуса-чека)
  - [🔗 Отправка СМС](https://github.com/igor-netFantom/robokassa-api#отправка-смс)
  - [🔗 Получение результата оплаты счета от Робокассы](https://github.com/igor-netFantom/robokassa-api#получение-результата-оплаты-счета-от-робокассы)
  - [🔗 Вспомогательные методы](https://github.com/igor-netFantom/robokassa-api#вспомогательные-методы)
    - [🔗 Параметры для отправки СМС](https://github.com/igor-netFantom/robokassa-api#получение-данных-для-отправки-смс)
    - [🔗 Формирование данных для отправки второго чека или проверки статуса чека](https://github.com/igor-netFantom/robokassa-api#получение-данных-для-отправки-второго-чека-или-проверки-статуса-чека)

### Переадресация на страницу оплаты счета

```php
/** @var \netFantom\Yii2Robokassa\Yii2Robokassa $robokassa */
$robokassa = Yii::$app->get('robokassa');

/** 
 * @var \models\Invoice $invoice смотрите пример модели счета в разделе ниже 
 * @see https://github.com/igor-netFantom/yii2-robokassa#пример-модели-счета
 */

/** @var bool $setReturnUrl по умолчанию TRUE {@see \yii\web\User::setReturnUrl()} */

$response = $robokassa->redirectToPaymentUrl($invoice->getInvoiceOptions(), $setReturnUrl);

/** @var \yii\web\Response $response */
return $response;
```

### Преобразование параметров платежа в поля формы

Преобразует параметры платежа `InvoiceOptions` в скрытые поля формы `Html::hiddenInput()` для отправки пользователя на
оплату `POST` запросом

```php
/** @var \netFantom\Yii2Robokassa\Yii2Robokassa $robokassa */
$robokassa = Yii::$app->get('robokassa');

/** 
 * @var \models\Invoice $invoice смотрите пример модели счета в разделе ниже 
 * @see https://github.com/igor-netFantom/yii2-robokassa#пример-модели-счета
 */
 
echo $robokassa->getHiddenInputsHtml($invoice->getInvoiceOptions());
```

### Получение результата оплаты счета от Робокассы из HTTP запроса Yii

```php
use netFantom\Yii2Robokassa\Yii2Robokassa;
use netFantom\RobokassaApi\Results\InvoicePayResult;

/** @var \yii\web\Request $request */
$request = Yii::$app->request

/** @var InvoicePayResult $invoicePayResult */
$invoicePayResult = Yii2Robokassa::getInvoicePayResultFromYiiWebRequest($request);
```

### Методы модуля `netFantom/robokassa-api`

Данный компонент является оберткой вокруг [`netFantom/robokassa-api`](https://github.com/igor-netFantom/robokassa-api)
и полностью исполняет его интерфейс `netFantom\robokassa-api\RobokassaApiInterface`:

( см. https://github.com/igor-netFantom/robokassa-api )

## Пример использования компонента

- [Пример модели счета](#пример-модели-счета)
- [Примеры действий контроллера для обработки запросов Робокассы](#примеры-действий-контроллера-для-обработки-запросов-робокассы)
- [Примеры представлений для создания счета и отправки пользователя на оплату](#примеры-представлений-для-создания-счета-и-отправки-пользователя-на-оплату)
  - [ВАРИАНТ: Загрузка Popup виджета оплаты AJAX запросом](#вариант--загрузка-popup-виджета-оплаты-ajax-запросом)
  - [ВАРИАНТ: Переход на оплату формой с POST запросом](#вариант--переход-на-оплату-формой-с-post-запросом)
  - [ВАРИАНТ: Формирование Popup виджета](#вариант--формирование-popup-виджета)

### Пример модели счета

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
use models\InvoiceStatus;
use DateInterval;
use DateTimeImmutable;
use netFantom\RobokassaApi\Options\InvoiceOptions;
use netFantom\RobokassaApi\Params\Option\{Culture, OutSumCurrency, Receipt};
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
            expirationDate: (new DateTimeImmutable())->add(new DateInterval('PT48H')),
            email: 'user@email.com',
            outSumCurrency: OutSumCurrency::USD,
            userIP: '127.0.0.1',
            incCurrLabel: null,
            userParameters: [
                'user_id' => '123',
                'parameter2' => 'parameter2_value',
                // ...
            ],
            encoding: 'utf-8',
            culture: Culture::ru,
        );
    }
    //...
}
```

### [Примеры действий контроллера](https://github.com/igor-netFantom/yii2-robokassa/blob/main/examples/controllers/PaymentController.php) для обработки запросов Робокассы

```php
use models\{Invoice, InvoiceStatus, PaymentSystem};
use netFantom\Yii2Robokassa\Assets\PopupIframeAsset;
use netFantom\Yii2Robokassa\Yii2Robokassa;
use Yii;
use yii\web\{BadRequestHttpException, Controller, Response};

class PaymentController extends Controller
{
    /**
     * В случае отказа от исполнения платежа покупатель перенаправляется по данному адресу.
     * Необходим для того, чтобы продавец мог, например, разблокировать заказанный товар на складе.
     *
     * Переход пользователя по данному адресу, строго говоря, не означает окончательного отказа покупателя от оплаты,
     * нажав кнопку «Назад» в браузере он может вернуться на страницу оплаты Robokassa.
     * Поэтому в случае блокировки товара на складе под заказ, для его разблокирования желательно проверять
     * факт отказа от платежа запросом XML-интерфейса получения состояния оплаты счета, используя в запросе
     * номер счета InvId имеющийся в базе данных магазина/продавца.
     * @link https://docs.robokassa.ru/pay-interface/
     */
    public function actionFail(): Response
    {
        $invoicePayResult = Yii2Robokassa::getInvoicePayResultFromYiiWebRequest(Yii::$app->request);
        $invoice = $this->loadInvoice($invoicePayResult->invId);

        if ($invoice->status_id === InvoiceStatus::STATUS_CREATED) {
            $invoice->updateAttributes(['status' => InvoiceStatus::STATUS_FAILED]);
        }
        return $this->goBack();
    }

    /**
     * ВАРИАНТ: Загрузка Popup виджета оплаты AJAX запросом
     */
    public function actionInvoiceAjax(): string
    {
        $invoice = new Invoice();
        $invoice->payment_system_id = PaymentSystem::SYSTEM_ROBOKASSA;
        $invoice->status_id = InvoiceStatus::STATUS_CREATED;
        $invoice->user_id = Yii::$app->user->id;

        if (Yii::$app->request->isAjax && $invoice->load(Yii::$app->request->post()) && $invoice->save()) {
            return $this->renderAjax('invoice-ajax-response', compact('invoice'));
        }

        PopupIframeAsset::register($this->view);
        return $this->render('invoice-ajax', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * ВАРИАНТ: Переход на оплату формой с POST запросом
     */
    public function actionInvoiceForm(int $id = null): string
    {
        if (isset($id)) {
            $invoice = $this->loadInvoice($id);
            if ($invoice->user_id !== Yii::$app->user->id || $invoice->status_id !== InvoiceStatus::STATUS_CREATED) {
                throw new BadRequestHttpException('Подходящий по условиям счёт не найден');
            }
            return $this->render('invoice-form', compact('invoice'));
        }

        $invoice = new Invoice();
        $invoice->payment_system_id = PaymentSystem::SYSTEM_ROBOKASSA;
        $invoice->status_id = InvoiceStatus::STATUS_CREATED;
        $invoice->user_id = Yii::$app->user->id;

        if ($invoice->load(Yii::$app->request->post()) && $invoice->save()) {
            $this->redirect(['payment/invoice-form', 'id' => $invoice->id]);
        }

        return $this->render('invoice-create', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * ВАРИАНТ: Формирование Popup виджета
     */
    public function actionInvoicePopup(int $id = null): string
    {
        if (isset($id)) {
            $invoice = $this->loadInvoice($id);
            if ($invoice->user_id !== Yii::$app->user->id || $invoice->status_id !== InvoiceStatus::STATUS_CREATED) {
                throw new BadRequestHttpException('Подходящий по условиям счёт не найден');
            }
            return $this->render('invoice-popup', compact('invoice'));
        }

        $invoice = new Invoice();
        $invoice->payment_system_id = PaymentSystem::SYSTEM_ROBOKASSA;
        $invoice->status_id = InvoiceStatus::STATUS_CREATED;
        $invoice->user_id = Yii::$app->user->id;

        if ($invoice->load(Yii::$app->request->post()) && $invoice->save()) {
            $this->redirect(['payment/invoice-popup', 'id' => $invoice->id]);
        }

        return $this->render('invoice-create', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * ResultURL предназначен для получения Вашим сайтом оповещения об успешном платеже в автоматическом режиме.
     * В случае успешного проведения оплаты Robokassa делает запрос на ResultURL (см. раздел Технические настройки).
     * Данные всегда передаются в кодировке UTF-8.
     *
     * Ваш скрипт, находящийся по ResultURL, обязан проверить равенство полученной контрольной суммы
     * и контрольной суммы, рассчитанной Вашим скриптом по параметрам, полученным от Robokassa,
     * а не по локальным данным магазина.
     *
     * Если контрольные суммы совпали, то Ваш скрипт должен ответить Robokassa, чтобы мы поняли,
     * что Ваш скрипт работает правильно и повторное уведомление с нашей стороны не требуется.
     * Результат должен содержать текст OK и параметр InvId.
     * Например, для номера счёта 5 должен быть вот такой ответ: OK5.
     *
     * Если контрольные суммы не совпали, то полученное оповещение некорректно, и ситуация требует разбора магазином.
     * @link https://docs.robokassa.ru/pay-interface/
     */
    public function actionResult(): string
    {
        /** @var Yii2Robokassa $robokassa */
        $robokassa = Yii::$app->get('robokassa');

        $invoicePayResult = Yii2Robokassa::getInvoicePayResultFromYiiWebRequest(Yii::$app->request);
        if (!$robokassa->checkSignature($invoicePayResult)) {
            throw new BadRequestHttpException();
        }

        if (!$this->loadInvoice($invoicePayResult->invId)->updateAttributes(['status' => InvoiceStatus::STATUS_PAYED])) {
            throw new BadRequestHttpException();
        }

        return $invoicePayResult->formatOkAnswer();
    }

    /**
     * В случае успешного исполнения платежа Покупатель сможет перейти по адресу,
     * указанному вами в Технических настройках, там же вы указали метод (GET или POST).
     *
     * Переход пользователя по данному адресу с корректными параметрами (правильной Контрольной суммой) означает,
     * что оплата вашего заказа успешно выполнена.
     *
     * Однако для дополнительной защиты желательно, чтобы факт оплаты проверялся скриптом,
     * исполняемым при переходе на SuccessURL, или путем запроса XML-интерфейса получения состояния оплаты счета,
     * и только при реальном наличии счета с номером InvId в базе данных магазина.
     *
     * На самом деле, переход пользователя по ссылке SuccessURL – это формальность, которая нужна только для того,
     * чтобы пользователь вернулся обратно к Вам и получил информацию о том, что он сделал всё правильно,
     * и его заказ ждёт его там-то и там-то. Проводить подтверждение оплаты у себя по базе и все остальные действия,
     * связанные с выдачей покупки, Вам нужно при получении уведомления на ResultUrl,
     * потому что именно на него Robokassa передаёт подтверждающие данные об оплате в автоматическом режиме
     * (т. е. в любом случае и без участия пользователя).
     * @link https://docs.robokassa.ru/pay-interface/
     */
    public function actionSuccess(): Response|string
    {
        $invoicePayResult = Yii2Robokassa::getInvoicePayResultFromYiiWebRequest(Yii::$app->request);
        $invoice = $this->loadInvoice($invoicePayResult->invId);

        return $this->render("success", compact('invoice'));
    }

    protected function loadInvoice(int $id): Invoice
    {
        $invoice = Invoice::find()
            ->andWhere(['id' => $id])
            ->andWhere(['payment_system_id' => PaymentSystem::SYSTEM_ROBOKASSA])
            ->one();
        if ($invoice === null) {
            throw new BadRequestHttpException('Подходящий по условиям счёт не найден');
        }
        return $invoice;
    }
}
```

### [Примеры представлений](https://github.com/igor-netFantom/yii2-robokassa/blob/main/examples/views) для создания счета и отправки пользователя на оплату

#### ВАРИАНТ: Загрузка Popup виджета оплаты AJAX запросом

Пример представления
[`invoice-ajax`](https://github.com/igor-netFantom/yii2-robokassa/blob/main/examples/views/payment/invoice-ajax.php)
для действия `actionInvoiceAjax`

```php
use models\Invoice;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $invoice Invoice */

$url = Url::current();

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

$form = ActiveForm::begin([
    'id' => 'pay-form',
    'enableClientValidation' => false,
]);
echo $form->field($invoice, 'sum')->textInput();
echo Html::submitButton('Pay', ['class' => 'btn btn-success']);
ActiveForm::end();
```

Пример представления
[`invoice-ajax-response`](https://github.com/igor-netFantom/yii2-robokassa/blob/main/examples/views/payment/invoice-ajax-response.php)
для действия `actionInvoiceAjax`

```php
use models\Invoice;
use netFantom\Yii2Robokassa\Widgets\PopupIframeWidget;
use netFantom\Yii2Robokassa\Yii2Robokassa;
use yii\helpers\Html;
use yii\web\View;

/* @var $this View */
/* @var $invoice Invoice */

/** @var Yii2Robokassa $robokassa */
$robokassa = Yii::$app->get('robokassa');

PopupIframeWidget::widget([
    'yii2Robokassa' => $robokassa,
    'invoiceOptions' => $invoice->getInvoiceOptions(),
    'registerAsset' => false,
]);

echo Html::encode("Сформирован счет №$invoice->id на сумму $invoice->sum руб. и ждет оплаты");
echo Html::button('оплатить', [
    'onClick' => PopupIframeWidget::SHOW_ROBOKASSA_POPUP_IFRAME_ACTION,
    'class' => 'btn btn-primary',
]);
```

#### ВАРИАНТ: Переход на оплату формой с POST запросом

Пример представления
[`invoice-form`](https://github.com/igor-netFantom/yii2-robokassa/blob/main/examples/views/payment/invoice-form.php)
для действия `actionInvoiceForm`

```php
use models\Invoice;
use netFantom\Yii2Robokassa\Widgets\PopupIframeWidget;
use netFantom\Yii2Robokassa\Yii2Robokassa;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;

/* @var $this View */
/* @var $invoice Invoice */

/** @var Yii2Robokassa $robokassa */
$robokassa = Yii::$app->get('robokassa');

echo Html::encode("Сформирован счет №$invoice->id на сумму $invoice->sum руб. и ждет оплаты");

$form = ActiveForm::begin([
    'id' => 'pay-form',
    'method' => 'POST',
    'action' => $robokassa->paymentUrl,
]);
echo $robokassa->getHiddenInputsHtml($invoice->getInvoiceOptions());
echo Html::submitButton('оплатить', [
    'onClick' => PopupIframeWidget::SHOW_ROBOKASSA_POPUP_IFRAME_ACTION,
    'class' => 'btn btn-primary btn-lg',
]);
ActiveForm::end();
```

#### ВАРИАНТ: Формирование Popup виджета

Пример представления
[`invoice-popup`](https://github.com/igor-netFantom/yii2-robokassa/blob/main/examples/views/payment/invoice-popup.php)
для действия `actionInvoicePopup`

```php
use models\Invoice;
use netFantom\Yii2Robokassa\Widgets\PopupIframeWidget;
use netFantom\Yii2Robokassa\Yii2Robokassa;
use yii\helpers\Html;
use yii\web\View;

/* @var $this View */
/* @var $invoice Invoice */

/** @var Yii2Robokassa $robokassa */
$robokassa = Yii::$app->get('robokassa');

PopupIframeWidget::widget([
    'yii2Robokassa' => $robokassa,
    'invoiceOptions' => $invoice->getInvoiceOptions(),
    'showOnLoad' => true,
]);
echo Html::encode("Сформирован счет №$invoice->id на сумму $invoice->sum руб. и ждет оплаты");
echo Html::button('оплатить', [
    'onClick' => PopupIframeWidget::SHOW_ROBOKASSA_POPUP_IFRAME_ACTION,
    'class' => 'btn btn-primary btn-lg',
]);
```

Пример представления
[`invoice-create`](https://github.com/igor-netFantom/yii2-robokassa/blob/main/examples/views/payment/invoice-create.php)
для действий `actionInvoicePopup` и `actionInvoiceForm`

```php
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
```