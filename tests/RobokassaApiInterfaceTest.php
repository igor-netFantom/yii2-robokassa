<?php /** @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace tests;

use Http\Discovery\Psr18Client;
use netFantom\RobokassaApi\Options\InvoiceOptions;
use netFantom\RobokassaApi\Options\ReceiptStatusOptions;
use netFantom\RobokassaApi\Options\SecondReceiptOptions;
use netFantom\RobokassaApi\Results\InvoicePayResult;
use netFantom\RobokassaApi\RobokassaApiInterface;
use netFantom\Yii2Robokassa\Yii2Robokassa;
use Nyholm\Psr7\Response;

class RobokassaApiInterfaceTest extends TestCase
{
    public function testCheckSignature(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('checkSignature');
        $this->getYii2Robokassa($robokassaApi)->checkSignature(new InvoicePayResult(0, 0, ''));
    }

    private function getYii2Robokassa(RobokassaApiInterface $robokassaApi): Yii2Robokassa
    {
        return new Yii2Robokassa([
            'robokassaApi' => $robokassaApi,
        ]);
    }

    public function testGetBase64SignedPostData(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('getBase64SignedPostData');
        $this->getYii2Robokassa($robokassaApi)->getBase64SignedPostData(new ReceiptStatusOptions(0));
    }

    public function testGetInvoicePayResultFromRequestArray(): void
    {
        $actual = Yii2Robokassa::getInvoicePayResultFromRequestArray([
            'OutSum' => '0',
            'SignatureValue' => '',
        ]);
        $expected = new InvoicePayResult(0, 0, '');
        $this->assertEquals($expected, $actual);
    }

    public function testGetPaymentParameters(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('getPaymentParameters');
        $this->getYii2Robokassa($robokassaApi)->getPaymentParameters(new InvoiceOptions(0, 0, ''));
    }

    public function testGetPaymentUrl(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('getPaymentUrl');
        $this->getYii2Robokassa($robokassaApi)->getPaymentUrl(new InvoiceOptions(0, 0, ''));
    }

    public function testGetPsr18Client(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->method('getPsr18Client')->willReturn(new Psr18Client());
        $robokassaApi->expects($this->once())->method('getPsr18Client');
        $this->getYii2Robokassa($robokassaApi)->getPsr18Client();
    }

    public function testGetReceiptAttachResult(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('getReceiptAttachResult');
        $this->getYii2Robokassa($robokassaApi)->getReceiptAttachResult(new Response());
    }

    public function testGetReceiptStatus(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('getReceiptStatus');
        $this->getYii2Robokassa($robokassaApi)->getReceiptStatus(new ReceiptStatusOptions(0));
    }

    public function testGetReceiptStatusResult(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('getReceiptStatusResult');
        $this->getYii2Robokassa($robokassaApi)->getReceiptStatusResult(new Response());
    }

    public function testGetSendSmsData(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('getSendSmsData');
        $this->getYii2Robokassa($robokassaApi)->getSendSmsData(0, '');
    }

    public function testGetSmsSendResult(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('getSmsSendResult');
        $this->getYii2Robokassa($robokassaApi)->getSmsSendResult(new Response());
    }

    public function testGetVatsFromItems(): void
    {
        $this->assertEquals([], Yii2Robokassa::getVatsFromItems([]));
    }

    public function testReceiptStatusRequest(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('receiptStatusRequest');
        $this->getYii2Robokassa($robokassaApi)->receiptStatusRequest(new ReceiptStatusOptions(0));
    }

    public function testSecondReceiptAttachRequest(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('secondReceiptAttachRequest');
        $this->getYii2Robokassa($robokassaApi)->secondReceiptAttachRequest(
            new SecondReceiptOptions(
                0,
                0,
                '',
                0,
                [],
                []
            )
        );
    }

    public function testSendSecondReceiptAttach(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('sendSecondReceiptAttach');
        $this->getYii2Robokassa($robokassaApi)->sendSecondReceiptAttach(
            new SecondReceiptOptions(
                0,
                0,
                '',
                0,
                [],
                []
            )
        );
    }

    public function testSendSms(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('sendSms');
        $this->getYii2Robokassa($robokassaApi)->sendSms(0, '');
    }

    public function testSetPsr18Client(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('setPsr18Client');
        $this->getYii2Robokassa($robokassaApi)->setPsr18Client(new Psr18Client());
    }

    public function testSmsRequest(): void
    {
        $robokassaApi = $this->createMock(RobokassaApiInterface::class);
        $robokassaApi->expects($this->once())->method('smsRequest');
        $this->getYii2Robokassa($robokassaApi)->smsRequest(0, '');
    }
}
