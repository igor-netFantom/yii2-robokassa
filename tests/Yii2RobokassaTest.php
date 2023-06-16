<?php

declare(strict_types=1);

namespace tests;

use DateTime;
use DateTimeZone;
use netFantom\RobokassaApi\Exceptions\TooLongSmsMessageException;
use netFantom\RobokassaApi\Options\Culture;
use netFantom\RobokassaApi\Options\InvoiceOptions;
use netFantom\RobokassaApi\Options\OutSumCurrency;
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

        $resultOptions = Yii2Robokassa::getResultOptionsFromRequest(Yii::$app->request);
        $this->assertTrue($this->getYii2Robokassa()->checkSignature($resultOptions));

        $this->mockWebApplication();

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['OutSum'] = 100;
        $_POST['InvId'] = 1;
        $_POST['SignatureValue'] = md5('100:1:wrong_password');

        $resultOptions = Yii2Robokassa::getResultOptionsFromRequest(Yii::$app->request);
        $this->assertFalse($this->getYii2Robokassa()->checkSignature($resultOptions));
    }

    public function testGetHiddenInputsHtml()
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

        $Yii2Robokassa = new Yii2Robokassa([
            'robokassaApi' => new RobokassaApi(
                merchantLogin: 'merchant',
                password1: 'password#1',
                password2: 'password#2',
                isTest: true,
                hashAlgo: 'md5',
                paymentUrl: 'https://auth.robokassa.kz/Merchant/Index.aspx',
            ),
        ]);
        $returnUrl = $Yii2Robokassa->getPaymentUrl(
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

    public function testPrepareSmsRequest()
    {
        $this->mockApplication();
        $request = $this->getYii2Robokassa()->prepareSmsRequest(1234567, 'message text');
        $expectedRequest = 'GET https://services.robokassa.ru/SMS/?login=robo-demo&phone=1234567'
            . '&message=message+text&signature=dc6be4ee3f069e79a2c983626363ec37';
        $this->assertEquals($expectedRequest, $request->toString());
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
    }

    public function testSendSms(): void
    {
        $this->expectException(TooLongSmsMessageException::class);
        $this->getYii2Robokassa()->sendSms(0, str_repeat('x', 129));
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
        ]);
        /** @var Yii2Robokassa $Yii2Robokassa */
        $Yii2Robokassa = Yii::$app->robokassa;
        $this->assertEquals(true, $Yii2Robokassa->isTest);
        $this->assertEquals(true, $Yii2Robokassa->robokassaApi->isTest);

        $Yii2Robokassa->isTest = false;
        $this->assertEquals(false, $Yii2Robokassa->isTest);
        $this->assertEquals(false, $Yii2Robokassa->robokassaApi->isTest);

        try {
            /** @noinspection PhpUndefinedFieldInspection */
            $Yii2Robokassa->notExistProperty = true;
        } catch (UnknownPropertyException $exception) {
            $this->assertStringContainsString('Setting unknown property', $exception->getMessage());
        }
        try {
            /** @noinspection PhpUnusedLocalVariableInspection */
            $notExistProperty = $Yii2Robokassa->notExistProperty;
        } catch (UnknownPropertyException $exception) {
            $this->assertStringContainsString('Getting unknown property', $exception->getMessage());
        }
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
        );

        $Yii2RobokassaFromArray = new Yii2Robokassa([
            'merchantLogin' => 'robo-demo',
            'password1' => 'password_1',
            'password2' => 'password_2',
            'isTest' => true,
            'hashAlgo' => 'md5',
        ]);
        $this->assertEquals($expectedRobokassa, $Yii2RobokassaFromArray->robokassaApi);

        $Yii2RobokassaFromObject = new Yii2Robokassa([
            'robokassaApi' => new RobokassaApi(
                merchantLogin: 'robo-demo',
                password1: 'password_1',
                password2: 'password_2',
                isTest: true,
                hashAlgo: 'md5',
            )
        ]);
        $this->assertEquals($expectedRobokassa, $Yii2RobokassaFromObject->robokassaApi);

        Yii::$app->set('robokassa', [
            'class' => Yii2Robokassa::class,
            'merchantLogin' => 'robo-demo',
            'password1' => 'password_1',
            'password2' => 'password_2',
            'isTest' => '1',
            'hashAlgo' => 'md5',
        ]);
        /** @var Yii2Robokassa $Yii2RobokassaFromComponentsConfig */
        $Yii2RobokassaFromComponentsConfig = Yii::$app->robokassa;
        $this->assertEquals($expectedRobokassa, $Yii2RobokassaFromComponentsConfig->robokassaApi);
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
}
