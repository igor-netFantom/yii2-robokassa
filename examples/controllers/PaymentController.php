<?php

/** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace controllers;

use models\Invoice;
use models\InvoiceStatus;
use models\PaymentSystem;
use netFantom\Yii2Robokassa\Assets\PopupIframeAsset;
use netFantom\Yii2Robokassa\Yii2Robokassa;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

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
        $resultOptions = Yii2Robokassa::getResultOptionsFromRequest(Yii::$app->request);
        $invoice = $this->loadInvoice($resultOptions->invId);

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

        /** @noinspection NotOptimalIfConditionsInspection */
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

        /** @noinspection NotOptimalIfConditionsInspection */
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

        /** @noinspection NotOptimalIfConditionsInspection */
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
        /** @var Yii2Robokassa $Yii2Robokassa */
        $Yii2Robokassa = Yii::$app->get('robokassa');

        $resultOptions = Yii2Robokassa::getResultOptionsFromRequest(Yii::$app->request);
        if (!$Yii2Robokassa->checkSignature($resultOptions)) {
            throw new BadRequestHttpException();
        }

        if (!$this->loadInvoice($resultOptions->invId)->updateAttributes(['status' => InvoiceStatus::STATUS_PAYED])) {
            throw new BadRequestHttpException();
        }

        return $resultOptions->formatOkAnswer();
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
        $resultOptions = Yii2Robokassa::getResultOptionsFromRequest(Yii::$app->request);
        $invoice = $this->loadInvoice($resultOptions->invId);

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