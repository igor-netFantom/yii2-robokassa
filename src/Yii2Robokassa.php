<?php

declare(strict_types=1);

namespace netFantom\Yii2Robokassa;

use JsonException;
use netFantom\RobokassaApi\Options\{InvoiceOptions, ReceiptStatusOptions, SecondReceiptOptions};
use netFantom\RobokassaApi\Results\{InvoicePayResult, ReceiptAttachResult, ReceiptStatusResult, SmsSendResult};
use netFantom\RobokassaApi\RobokassaApi;
use netFantom\RobokassaApi\RobokassaApiInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\{RequestFactoryInterface, RequestInterface, ResponseInterface, StreamFactoryInterface};
use ReflectionClass;
use RuntimeException;
use Yii;
use yii\base\{Component, InvalidConfigException, InvalidRouteException, UnknownPropertyException};
use yii\helpers\Html;
use yii\web\{Application, Request, Response};

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
class Yii2Robokassa extends Component implements RobokassaApiInterface
{
    /**
     *  Если передан параметр
     * ```
     * [
     *      'robokassaApi'=>new \robokassaApi\RobokassaApi(...),
     * ]
     * ```
     * то передача параметров {@see RobokassaApi} в форме массива игнорируются.
     * @var RobokassaApiInterface
     */
    public RobokassaApiInterface $robokassaApi;
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
     * @var array{isTest?: bool, psr18Client?: ClientInterface|null, ...<string,string>}
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
             * @psalm-var (array{merchantLogin: string, password1: string,password2: string, isTest?: bool, psr18Client?: ClientInterface, ...<string,string>})
             * $robokassaApiInitAttributes
             */
            $this->robokassaApi = new RobokassaApi(
                ...$robokassaApiInitAttributes
            );
        }
    }

    /**
     * Получение параметров результата {@see InvoicePayResult} от Робокассы
     * из GET или POST параметров HTTP запроса {@see Request::getInvoicePayResultFromArray()}
     */
    public static function getInvoicePayResultFromRequest(Request $request): InvoicePayResult
    {
        /** @var array<string, string> $requestParameters */
        $requestParameters = $request->isPost ? $request->post() : $request->get();
        return RobokassaApi::getInvoicePayResultFromRequestArray($requestParameters);
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
     * Инициализация и изменение свойств {@see RobokassaApi} через {@see Yii2Robokassa}
     * @throws InvalidConfigException
     */
    private function setRobokassaApiAttributes(string $name, mixed $value): void
    {
        $valueType = gettype($value);
        /**
         * @psalm-suppress RedundantPropertyInitializationCheck
         * This method could be called from Yii parent::__construct() and on existing object by __set()
         */
        if (isset($this->robokassaApi)) {
            $this->robokassaApi->$name = $value;
        } elseif ($name === 'isTest') {
            if (!is_numeric($value) && !is_bool($value)) {
                throw new InvalidConfigException(
                    "$name property of " . self::class . " must be boolean or numeric, but `$valueType` provided"
                );
            }
            $this->robokassaApiInitAttributes[$name] = (bool)$value;
        } elseif ($name === 'psr18Client') {
            if (is_null($value)) {
                return;
            }
            if (!is_array($value) && !is_callable($value) && !is_string($value) && !is_object($value)) {
                throw new InvalidConfigException(
                    "$name property of " . self::class . " must be array|callable|string|object, but `$valueType` provided"
                );
            }
            if (!is_object($value)) {
                $value = Yii::createObject($value);
            }
            if (
                (!$value instanceof ClientInterface)
                || (!$value instanceof RequestFactoryInterface)
                || (!$value instanceof StreamFactoryInterface)
            ) {
                throw new RuntimeException(
                    'psr18Client must be PSR-18 HTTP Client providing '
                    . 'psr/http-client-implementation, psr/http-factory-implementation and psr/http-message-implementation '
                    . 'and implementing ClientInterface, RequestFactoryInterface and StreamFactoryInterface'
                );
            }
            $this->robokassaApiInitAttributes[$name] = $value;
        } elseif (is_string($value)) {
            $this->robokassaApiInitAttributes[$name] = $value;
        } else {
            throw new InvalidConfigException(
                "$name property of " . self::class . " must be string, but `$valueType` provided"
            );
        }
    }

    /**
     * Проверка корректности подписи параметров результата {@see InvoicePayResult} от Робокассы
     */
    public function checkSignature(InvoicePayResult $invoicePayResult): bool
    {
        return $this->robokassaApi->checkSignature($invoicePayResult);
    }

    /**
     * Получение URL для оплаты счета с указанными параметрами
     */
    public function getPaymentUrl(InvoiceOptions $invoiceOptions): string
    {
        return $this->robokassaApi->getPaymentUrl($invoiceOptions);
    }

    public function sendSms(int $phone, string $message): ResponseInterface
    {
        return $this->robokassaApi->sendSms($phone, $message);
    }

    public static function getInvoicePayResultFromRequestArray(array $requestParameters): InvoicePayResult
    {
        return RobokassaApi::getInvoicePayResultFromRequestArray($requestParameters);
    }

    public static function getVatsFromItems(array $items): array
    {
        return RobokassaApi::getVatsFromItems($items);
    }

    public function getPaymentParameters(InvoiceOptions $invoiceOptions): array
    {
        return $this->robokassaApi->getPaymentParameters($invoiceOptions);
    }

    public function receiptStatusRequest(ReceiptStatusOptions $receiptStatusOptions): RequestInterface
    {
        return $this->robokassaApi->receiptStatusRequest($receiptStatusOptions);
    }

    public function getPsr18Client(): RequestFactoryInterface&ClientInterface&StreamFactoryInterface
    {
        return $this->robokassaApi->getPsr18Client();
    }

    public function getBase64SignedPostData(SecondReceiptOptions|ReceiptStatusOptions $options): string
    {
        return $this->robokassaApi->getBase64SignedPostData($options);
    }

    public function secondReceiptAttachRequest(SecondReceiptOptions $secondReceiptOptions): RequestInterface
    {
        return $this->robokassaApi->secondReceiptAttachRequest($secondReceiptOptions);
    }

    public function setPsr18Client(?ClientInterface $psr18Client): void
    {
        $this->robokassaApi->setPsr18Client($psr18Client);
    }

    public function getReceiptAttachResult(ResponseInterface $response): ReceiptAttachResult
    {
        return $this->robokassaApi->getReceiptAttachResult($response);
    }

    public function getReceiptStatus(ReceiptStatusOptions $secondReceiptOptions): ResponseInterface
    {
        return $this->robokassaApi->getReceiptStatus($secondReceiptOptions);
    }

    public function getReceiptStatusResult(ResponseInterface $response): ReceiptStatusResult
    {
        return $this->robokassaApi->getReceiptStatusResult($response);
    }

    public function getSendSmsData(int $phone, string $message): array
    {
        return $this->robokassaApi->getSendSmsData($phone, $message);
    }

    public function getSmsSendResult(ResponseInterface $response): SmsSendResult
    {
        return $this->robokassaApi->getSmsSendResult($response);
    }

    public function sendSecondReceiptAttach(SecondReceiptOptions $secondReceiptOptions): ResponseInterface
    {
        return $this->robokassaApi->sendSecondReceiptAttach($secondReceiptOptions);
    }

    public function smsRequest(int $phone, string $message): RequestInterface
    {
        return $this->robokassaApi->smsRequest($phone, $message);
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
        $paymentParameters = $this->getPaymentParameters($invoiceOptions);
        foreach ($paymentParameters as $parameterName => $parameterValue) {
            /** var BaseHtml $htmlHelperClass */
            $content .= Html::hiddenInput($parameterName, $parameterValue);
        }
        return $content;
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
}
