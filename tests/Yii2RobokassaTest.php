<?php

declare(strict_types=1);

namespace tests;

use DateTime;
use DateTimeZone;
use Http\Discovery\Psr18Client;
use netFantom\RobokassaApi\Options\InvoiceOptions;
use netFantom\RobokassaApi\Params\Culture;
use netFantom\RobokassaApi\Params\OutSumCurrency;
use netFantom\RobokassaApi\RobokassaApi;
use netFantom\Yii2Robokassa\Yii2Robokassa;
use Yii;
use yii\base\UnknownPropertyException;

/**
 * @group robokassa
 * @group Yii2Robokassa
 */
class Yii2RobokassaTest extends TestCase
{
    public function testCheckSignature(): void
    {
        $this->mockWebApplication();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['OutSum'] = 100;
        $_POST['InvId'] = 1;
        $_POST['SignatureValue'] = md5('100:1:password_2');

        $invoicePayResult = Yii2Robokassa::getInvoicePayResultFromYiiWebRequest(Yii::$app->request);
        $this->assertTrue($this->getYii2Robokassa()->checkSignature($invoicePayResult));

        $this->mockWebApplication();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['OutSum'] = 100;
        $_POST['InvId'] = 1;
        $_POST['SignatureValue'] = md5('100:1:wrong_password');

        $invoicePayResult = Yii2Robokassa::getInvoicePayResultFromYiiWebRequest(Yii::$app->request);
        $this->assertFalse($this->getYii2Robokassa()->checkSignature($invoicePayResult));
    }

    private function getYii2Robokassa(): Yii2Robokassa
    {
        return new Yii2Robokassa([
            'robokassaApi' => new RobokassaApi(
                merchantLogin: 'robo-demo',
                password1: 'password_1',
                password2: 'password_2',
                isTest: true,
            )
        ]);
    }

    public function testGetHiddenInputsHtml(): void
    {
        $invoiceOptions = new InvoiceOptions(
            outSum: "99",
            invId: null,
            description: 'Description 2',
            expirationDate: new DateTime('2030-01-01 10:20:30', new DateTimeZone('+3')),
            email: 'test@example.ru',
            outSumCurrency: OutSumCurrency::USD,
            userIP: '127.0.0.1',
            userParameters: ['email' => 'test@example.ru'],
            encoding: 'utf-8',
            culture: Culture::en,
        );
        $html = $this->getYii2Robokassa()->getHiddenInputsHtml($invoiceOptions);
        $expectedHtml = '<input type="hidden" name="MerchantLogin" value="robo-demo">'
            . '<input type="hidden" name="OutSum" value="99.00">'
            . '<input type="hidden" name="Description" value="Description 2">'
            . '<input type="hidden" name="SignatureValue" value="471944902523ea8252a0914dc9955bab">'
            . '<input type="hidden" name="IncCurrLabel">'
            . '<input type="hidden" name="InvId">'
            . '<input type="hidden" name="Culture" value="en">'
            . '<input type="hidden" name="Encoding" value="utf-8">'
            . '<input type="hidden" name="Email" value="test@example.ru">'
            . '<input type="hidden" name="ExpirationDate" value="2030-01-01T10:20:30.0000000+03:00">'
            . '<input type="hidden" name="OutSumCurrency" value="USD">'
            . '<input type="hidden" name="UserIp" value="127.0.0.1">'
            . '<input type="hidden" name="Receipt">'
            . '<input type="hidden" name="IsTest" value="1">'
            . '<input type="hidden" name="shp_email" value="test@example.ru">';
        $this->assertEquals($expectedHtml, $html);
    }

    public function testGetPaymentUrl(): void
    {
        $returnUrl = $this->getYii2Robokassa()->getPaymentUrl(
            new InvoiceOptions(
                outSum: 100,
                invId: null,
                description: 'description',
            ),
        );
        $expected = 'https://auth.robokassa.ru/Merchant/Index.aspx?MerchantLogin=robo-demo&OutSum=100.00'
            . '&Description=description&SignatureValue=0fa205254fbee0b1fcc53ffd2a1a38ba&Encoding=utf-8&IsTest=1';
        $this->assertEquals($expected, $returnUrl);

        $robokassa = new Yii2Robokassa([
            'robokassaApi' => new RobokassaApi(
                merchantLogin: 'merchant',
                password1: 'password#1',
                password2: 'password#2',
                isTest: true,
                hashAlgo: 'md5',
                paymentUrl: 'https://auth.robokassa.kz/Merchant/Index.aspx',
            ),
        ]);
        $returnUrl = $robokassa->getPaymentUrl(
            new InvoiceOptions(
                outSum: "99",
                invId: null,
                description: 'Description 2',
                expirationDate: new DateTime('2030-01-01 10:20:30', new DateTimeZone('+3')),
                email: 'test@example.ru',
                outSumCurrency: OutSumCurrency::USD,
                userIP: '127.0.0.1',
                userParameters: ['email' => 'test@example.ru'],
                encoding: 'utf-8',
                culture: Culture::en,
            ),
        );
        $expected = 'https://auth.robokassa.kz/Merchant/Index.aspx?MerchantLogin=merchant&OutSum=99.00'
            . '&Description=Description+2&SignatureValue=af11d6fb2c5f91225b9c00e01a6ea2cc&Culture=en&Encoding=utf-8'
            . '&Email=test%40example.ru&ExpirationDate=2030-01-01T10%3A20%3A30.0000000%2B03%3A00&OutSumCurrency=USD'
            . '&UserIp=127.0.0.1&IsTest=1&shp_email=test%40example.ru';
        $this->assertEquals($expected, $returnUrl);
    }

    public function testRedirectToPaymentUrl(): void
    {
        $this->mockWebApplication([
            'components' => [
                'request' => [
                    'url' => '/',
                ],
                'user' => [
                    'identityClass' => '\\tests\\User',
                ],
            ]
        ]);

        $response = $this->getYii2Robokassa()->redirectToPaymentUrl(
            new InvoiceOptions(
                outSum: 100,
                invId: 1,
                description: 'Description',
            )
        );

        $this->assertEquals(
            'https://auth.robokassa.ru/Merchant/Index.aspx?MerchantLogin=robo-demo&OutSum=100.00'
            . '&Description=Description&SignatureValue=8ca1d1c1a6f9353bebe5b087697ba797&InvId=1&Encoding=utf-8&IsTest=1',
            $response->getHeaders()->get('Location')
        );

        $this->mockApplication([
            'components' => [
                'request' => [
                    'url' => '/',
                ],
            ]
        ]);

        $this->expectExceptionMessage('yii\web\Application required to redirect');
        $this->getYii2Robokassa()->redirectToPaymentUrl(
            new InvoiceOptions(
                outSum: 100,
                invId: 1,
                description: 'Description',
            )
        );
    }

    public function testSettingAndGettingProperties(): void
    {
        $this->mockWebApplication();

        Yii::$app->set('robokassa', [
            'class' => Yii2Robokassa::class,
            'merchantLogin' => 'robo-demo',
            'password1' => 'password_1',
            'password2' => 'password_2',
            'hashAlgo' => 'md5',
            'isTest' => true,
            'psr18Client' => null,
        ]);
        /** @var Yii2Robokassa $robokassa */
        $robokassa = Yii::$app->robokassa;
        $this->assertEquals(true, $robokassa->isTest);
        $this->assertEquals(true, $robokassa->robokassaApi->isTest);

        $robokassa->isTest = false;
        $this->assertEquals(false, $robokassa->isTest);
        $this->assertEquals(false, $robokassa->robokassaApi->isTest);

        try {
            /** @noinspection PhpUndefinedFieldInspection */
            $robokassa->notExistProperty = true;
        } catch (UnknownPropertyException $exception) {
            $this->assertStringContainsString('Setting unknown property', $exception->getMessage());
        }
        try {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $notExistProperty = $robokassa->notExistProperty;
        } catch (UnknownPropertyException $exception) {
            $this->assertStringContainsString('Getting unknown property', $exception->getMessage());
        }
    }

    public function testWrongPsr18ClientConfig()
    {
        $this->mockWebApplication();

        Yii::$app->set('robokassa', [
            'class' => Yii2Robokassa::class,
            'merchantLogin' => 'robo-demo',
            'password1' => 'password_1',
            'password2' => 'password_2',
            'psr18Client' => true,
        ]);

        $message = "psr18Client property of netFantom\Yii2Robokassa\Yii2Robokassa "
            . "must be array|callable|string|object, but `boolean` provided";
        $this->expectExceptionMessage($message);
        Yii::$app->get('robokassa');
    }

    public function testWrongPsr18ClientObject()
    {
        $this->mockWebApplication();

        Yii::$app->set('robokassa', [
            'class' => Yii2Robokassa::class,
            'merchantLogin' => 'robo-demo',
            'password1' => 'password_1',
            'password2' => 'password_2',
            'psr18Client' => new \stdClass(),
        ]);

        $message = "psr18Client must be PSR-18 HTTP Client providing psr/http-client-implementation, "
            . "psr/http-factory-implementation and psr/http-message-implementation and implementing ClientInterface, "
            . "RequestFactoryInterface and StreamFactoryInterface";
        $this->expectExceptionMessage($message);
        Yii::$app->get('robokassa');
    }

    public function testYii2Robokassa(): void
    {
        $this->mockWebApplication();

        $expectedRobokassa = new RobokassaApi(
            merchantLogin: 'robo-demo',
            password1: 'password_1',
            password2: 'password_2',
            isTest: true,
            hashAlgo: 'md5',
            psr18Client: new Psr18Client(),
        );

        $robokassaFromArray = new Yii2Robokassa([
            'merchantLogin' => 'robo-demo',
            'password1' => 'password_1',
            'password2' => 'password_2',
            'isTest' => true,
            'hashAlgo' => 'md5',
            'psr18Client' => Psr18Client::class,
        ]);
        $this->assertEquals($expectedRobokassa, $robokassaFromArray->robokassaApi);

        $robokassaFromObject = new Yii2Robokassa([
            'robokassaApi' => new RobokassaApi(
                merchantLogin: 'robo-demo',
                password1: 'password_1',
                password2: 'password_2',
                isTest: true,
                hashAlgo: 'md5',
                psr18Client: new Psr18Client(),
            )
        ]);
        $this->assertEquals($expectedRobokassa, $robokassaFromObject->robokassaApi);

        Yii::$app->set('robokassa', [
            'class' => Yii2Robokassa::class,
            'merchantLogin' => 'robo-demo',
            'password1' => 'password_1',
            'password2' => 'password_2',
            'isTest' => '1',
            'hashAlgo' => 'md5',
            'psr18Client' => Psr18Client::class,
        ]);
        /** @var Yii2Robokassa $robokassaFromComponentsConfig */
        $robokassaFromComponentsConfig = Yii::$app->robokassa;
        $this->assertEquals($expectedRobokassa, $robokassaFromComponentsConfig->robokassaApi);
    }

    public function testYii2RobokassaWithWrongIsTestProperty(): void
    {
        $this->mockWebApplication();
        $this->expectExceptionMessage(
            'isTest property of netFantom\Yii2Robokassa\Yii2Robokassa must be boolean or numeric'
        );
        Yii::$app->set('robokassa', [
            'class' => Yii2Robokassa::class,
            'merchantLogin' => 'robo-demo',
            'password1' => 'password_1',
            'password2' => 'password_2',
            'isTest' => 'true',
            'hashAlgo' => 'md5',
        ]);
        Yii::$app->get('robokassa');
    }

    public function testYii2RobokassaWithWrongPassword1Property(): void
    {
        $this->mockWebApplication();
        $this->expectExceptionMessage('password1 property of netFantom\Yii2Robokassa\Yii2Robokassa must be string');
        Yii::$app->set('robokassa', [
            'class' => Yii2Robokassa::class,
            'merchantLogin' => 'robo-demo',
            'password1' => 123,
            'password2' => 'password_2',
            'isTest' => 1,
            'hashAlgo' => 'md5',
        ]);
        Yii::$app->get('robokassa');
    }

    public function testYii2RobokassaWithoutMerchantLogin(): void
    {
        $this->mockWebApplication();
        $this->expectExceptionMessage('merchantLogin property of netFantom\Yii2Robokassa\Yii2Robokassa required');
        Yii::$app->set('robokassa', [
            'class' => Yii2Robokassa::class,
            'password1' => 'password_1',
            'password2' => 'password_2',
            'isTest' => '1',
            'hashAlgo' => 'md5',
        ]);
        Yii::$app->get('robokassa');
    }
}
