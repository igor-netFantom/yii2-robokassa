<?php

declare(strict_types=1);

namespace netFantom\Yii2Robokassa;

use JsonException;
use netFantom\RobokassaApi\Options\InvoiceOptions;
use netFantom\RobokassaApi\Options\ResultOptions;
use netFantom\RobokassaApi\RobokassaApi;
use ReflectionClass;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\base\UnknownPropertyException;
use yii\helpers\Html;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\httpclient\Response as httpclientResponse;
use yii\web\Application;
use yii\web\Request;
use yii\web\Response;

/**
 * Обертка вокруг {@see RobokassaApi} с методами для работы в Yii framework.
 *
 * При создании требует обязательные параметры:
 * - $merchantLogin
 * - $password1
 * - $password2
 * @property string $merchantLogin Идентификатор магазина (игнорируется если передан $robokassaApi)
 * @property string $password1 Пароль №1 - для формирования подписи запроса (игнорируется если передан $robokassaApi)
 * @property string $password2 Пароль №2 - для проверки подписи ответа (игнорируется если передан $robokassaApi)
 * @property bool $isTest Для работы в тестовом режиме (игнорируется если передан $robokassaApi)
 * @property string $hashAlgo Алгоритм хеширования
 * @property string $paymentUrl URL оплаты
 * @property string $secondReceiptAttachUrl URL Формирования второго чека (В РАЗРАБОТКЕ)
 * @property string $secondReceiptStatusUrl URL Получение статуса чека (В РАЗРАБОТКЕ)
 * @property string $smsUrl URL отправки SMS
 * @property string $recurringUrl URL периодических платежей (В РАЗРАБОТКЕ)
 * @property string $splitPaymentUrl URL для сплитования (разделения) платежей (В РАЗРАБОТКЕ)
 */
class Yii2Robokassa extends Component
{
    /**
     *  Если передан параметр
     * ```
     * [
     *      'robokassaApi'=>new \robokassaApi\RobokassaApi(...),
     * ]
     * ```
     * то передача параметров {@see RobokassaApi} в форме массива игнорируются.
     * @var RobokassaApi
     */
    public RobokassaApi $robokassaApi;
    /**
     * Используется для создания {@see RobokassaApi}, если параметры переданы в формате массива.
     * ```php
     * 'components' => [
     *     'robokassa' => [
     *         'class' => 'netFantom\Yii2Robokassa\Yii2Robokassa',
     *         'merchantLogin' => 'robo-demo',
     *         'password1' => 'password1',
     *         'password2' => 'password2',
     *         'isTest' => !YII_ENV_PROD,
     *     ],
     *     // ...
     * ],
     * ```
     * Заполняется свойствами доступными в {@see RobokassaApi}, если {@see Yii2Robokassa::$robokassaApi} не инициализирован.
     * @var array{isTest?: bool, ...<string,string>}
     */
    protected array $robokassaApiInitAttributes = [];

    /**
     * @throws InvalidConfigException
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        /**
         * @psalm-suppress RedundantPropertyInitializationCheck
         * Yii could already set robokassaApi in parent::__construct()
         */
        if (!isset($this->robokassaApi)) {
            $RobokassaApiReflection = new ReflectionClass(RobokassaApi::class);
            $RobokassaApiArguments = $RobokassaApiReflection->getConstructor()?->getParameters()
            or throw new InvalidConfigException("Unexpected error: RobokassaApi don't have constructor");
            $robokassaApiInitAttributes = $this->robokassaApiInitAttributes;
            foreach ($RobokassaApiArguments as $argument) {
                if (!isset($robokassaApiInitAttributes[$argument->name]) && !$argument->isOptional()) {
                    throw new InvalidConfigException("$argument->name property of " . self::class . " required");
                }
            }
            /**
             * @psalm-var (array{merchantLogin: string, password1: string,password2: string, isTest?: bool, ...<string,string>})
             * $robokassaApiInitAttributes
             */
            $this->robokassaApi = new RobokassaApi(
                ...$robokassaApiInitAttributes
            );
        }
    }

    /**
     * Получение параметров результата {@see ResultOptions} от Робокассы
     * из GET или POST параметров HTTP запроса {@see Request::getResultOptionsFromArray()}
     */
    public static function getResultOptionsFromRequest(Request $request): ResultOptions
    {
        /** @var array<string, string> $requestParameters */
        $requestParameters = $request->isPost ? $request->post() : $request->get();
        return RobokassaApi::getResultOptionsFromRequestArray($requestParameters);
    }

    /**
     * Проверка корректности подписи параметров результата {@see ResultOptions} от Робокассы
     */
    public function checkSignature(ResultOptions $resultOptions): bool
    {
        return $this->robokassaApi->checkSignature($resultOptions);
    }

    /**
     * @param InvoiceOptions $invoiceOptions
     * @return string
     * @throws JsonException
     */
    public function getHiddenInputsHtml(
        InvoiceOptions $invoiceOptions
    ): string {
        $content = '';
        $paymentParameters = $this->paymentParameters($invoiceOptions);
        foreach ($paymentParameters as $parameterName => $parameterValue) {
            /** var BaseHtml $htmlHelperClass */
            $content .= Html::hiddenInput($parameterName, $parameterValue);
        }
        return $content;
    }

    /**
     * Получение URL для оплаты счета с указанными параметрами
     */
    public function getPaymentUrl(InvoiceOptions $invoiceOptions): string
    {
        return $this->robokassaApi->getPaymentUrl($invoiceOptions);
    }

    /**
     * Получает параметры платежа для передачи в Робокассу
     * (для формирования формы оплаты с методом передачи POST запросом)
     * @param InvoiceOptions $invoiceOptions
     * @return array<string, null|string>
     * @throws JsonException
     */
    public function paymentParameters(InvoiceOptions $invoiceOptions): array
    {
        return $this->robokassaApi->getPaymentParameters($invoiceOptions);
    }

    public function prepareSmsRequest(int $phone, string $message): \yii\httpclient\Request
    {
        $requestData = $this->robokassaApi->getSendSmsRequestData($phone, $message);
        return (new Client())->get($this->smsUrl, $requestData);
    }

    /**
     * Переадресация на страницу оплаты счета
     * @throws InvalidRouteException|InvalidConfigException
     */
    public function redirectToPaymentUrl(InvoiceOptions $invoiceOptions, bool $setReturnUrl = true): Response
    {
        /** @var Application $webApplication */
        $webApplication = Yii::$app;
        if (!($webApplication instanceof Application)) {
            throw new InvalidConfigException('yii\web\Application required to redirect');
        }
        if ($setReturnUrl) {
            $webApplication->user->setReturnUrl($webApplication->request->getUrl());
        }
        return $webApplication->response->redirect($this->robokassaApi->getPaymentUrl($invoiceOptions));
    }

    /**
     * @param int $phone Номер телефона в международном формате без символа «+». Например, 8999*******.
     * @param string $message строка в кодировке UTF-8 длиной до 128 символов, содержащая текст отправляемого SMS.
     * @return httpclientResponse Можно использовать для проверки ответа сервера
     * @throws Exception
     */
    public function sendSms(int $phone, string $message): httpclientResponse
    {
        return $this->prepareSmsRequest($phone, $message)->send();
    }

    /**
     * Инициализация и изменение свойств {@see RobokassaApi} через {@see Yii2Robokassa}
     * @throws InvalidConfigException
     */
    public function setRobokassaApiAttributes(string $name, mixed $value): void
    {
        /**
         * @psalm-suppress RedundantPropertyInitializationCheck
         * This method could be called from Yii parent::__construct() and on existing object by __set()
         */
        if (isset($this->robokassaApi)) {
            $this->robokassaApi->$name = $value;
        } elseif ($name === 'isTest') {
            if (!is_numeric($value) && !is_bool($value)) {
                throw new InvalidConfigException(
                    "$name property of " . self::class . " must be boolean or numeric, but `$value` provided"
                );
            }
            $this->robokassaApiInitAttributes[$name] = (bool)$value;
        } elseif (is_string($value)) {
            $this->robokassaApiInitAttributes[$name] = $value;
        } else {
            throw new InvalidConfigException(
                "$name property of " . self::class . " must be string, but `$value` provided"
            );
        }
    }

    /**
     * Расширяет {@see Component::__set}, добавляя инициализацию
     * и изменение свойств {@see RobokassaApi} через {@see Yii2Robokassa}
     * @throws UnknownPropertyException
     * @throws InvalidConfigException
     */
    public function __set($name, $value): void
    {
        if (property_exists(RobokassaApi::class, $name)) {
            $this->setRobokassaApiAttributes($name, $value);
            return;
        }
        parent::__set($name, $value);
    }

    /**
     * Расширяет {@see Component::__get}, добавляя
     * получение свойств {@see RobokassaApi} через {@see Yii2Robokassa}
     * @throws UnknownPropertyException
     */
    public function __get($name): mixed
    {
        if (property_exists(RobokassaApi::class, $name)) {
            return $this->robokassaApi->$name;
        }
        return parent::__get($name);
    }
}
