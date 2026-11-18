<?php
/**
 * Double-invoice race: every invoice writer (redirect/webhook processOrder and
 * the payment-status cron) must win the same atomic order claim, and the claim
 * must fail closed.
 *
 * Mocked persistence: this proves control flow, not database locking.
 */
namespace Psr\Log {
    // Composer vendor code is absent outside an installed store; declare the one method the cron uses.
    if (!interface_exists(LoggerInterface::class)) {
        interface LoggerInterface
        {
            public function debug($message, array $context = []);
        }
    }
}

namespace Amazonpaymentservices\Fort\Test\Unit\Invoice {

use Amazonpaymentservices\Fort\Cron\Paymentstatus;
use Amazonpaymentservices\Fort\Helper\Data;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class OrderClaimTest extends TestCase
{
    private function helperWithConnection($connection): Data
    {
        $helper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->onlyMethods(['log'])->getMock();
        $resource = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)->disableOriginalConstructor()
            ->onlyMethods(['getConnection', 'getTableName'])->getMock();
        if ($connection instanceof \Throwable) {
            $resource->method('getConnection')->willThrowException($connection);
        } else {
            $resource->method('getConnection')->willReturn($connection);
        }
        $resource->method('getTableName')->willReturnArgument(0);
        $prop = new ReflectionProperty(Data::class, '_connection');
        $prop->setAccessible(true);
        $prop->setValue($helper, $resource);
        return $helper;
    }

    private function order(): Order
    {
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()
            ->onlyMethods(['getEntityId', 'setState', 'setStatus'])->getMock();
        $order->method('getEntityId')->willReturn(12);
        return $order;
    }

    public function testClaimFailsClosedWhenTheDatabaseErrors(): void
    {
        $helper = $this->helperWithConnection(new \RuntimeException('db down'));
        $this->assertFalse($helper->claimOrderForProcessing($this->order()));
    }

    public function testClaimIsAConditionalUpdateAndOnlyOneCallerWins(): void
    {
        $connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $connection->expects($this->exactly(2))->method('update')
            ->with('sales_order', $this->anything(), $this->callback(function ($where) {
                return $where['entity_id = ?'] === 12
                    && $where['state NOT IN (?)'] === [Order::STATE_PROCESSING, Order::STATE_COMPLETE];
            }))
            ->willReturnOnConsecutiveCalls(1, 0);
        $helper = $this->helperWithConnection($connection);
        $this->assertTrue($helper->claimOrderForProcessing($this->order()));
        $this->assertFalse($helper->claimOrderForProcessing($this->order()));
    }

    public function testReleaseRestoresTheGivenStateAndStatus(): void
    {
        $connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $connection->expects($this->once())->method('update')
            ->with('sales_order', ['state' => Order::STATE_NEW, 'status' => 'pending'], $this->anything());
        $helper = $this->helperWithConnection($connection);
        $helper->releaseOrderProcessingClaim($this->order(), Order::STATE_NEW, 'pending');
    }

    /**
     * @return array{0: Paymentstatus, 1: Data, 2: Order}
     */
    private function cron(): array
    {
        $helper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()
            ->onlyMethods(['checkOrderStatus', 'log', 'claimOrderForProcessing', 'handleSendingInvoice', 'releaseOrderProcessingClaim'])
            ->getMock();
        $helper->method('checkOrderStatus')->willReturn([
            'response_code' => '12000',
            'transaction_code' => Data::PAYMENT_METHOD_PURCHASE_SUCCESS_STATUS,
        ]);
        $payment = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)->disableOriginalConstructor()
            ->onlyMethods(['getMethod'])->getMock();
        $payment->method('getMethod')->willReturn(\Amazonpaymentservices\Fort\Model\Method\Cc::CODE);
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()
            ->onlyMethods(['getStore', 'getPayment', 'getIncrementId', 'getState', 'getStatus', 'setState', 'setStatus', 'save', 'addStatusToHistory', 'getId'])
            ->getMock();
        $order->method('getPayment')->willReturn($payment);
        $order->method('getIncrementId')->willReturn('000000012');
        $order->method('getState')->willReturn(Order::STATE_NEW);
        $order->method('getStatus')->willReturn('pending');
        $order->method('setState')->willReturnSelf();
        $order->method('setStatus')->willReturnSelf();
        $cron = new Paymentstatus(
            $this->getMockBuilder(\Magento\Sales\Model\ResourceModel\Order\CollectionFactory::class)->disableOriginalConstructor()->getMock(),
            $this->createMock(\Psr\Log\LoggerInterface::class),
            $helper,
            $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock()
        );
        return [$cron, $helper, $order];
    }

    private function runOrderUpdate(Paymentstatus $cron, Order $order): void
    {
        $method = new ReflectionMethod(Paymentstatus::class, 'orderUpdate');
        $method->setAccessible(true);
        $method->invoke($cron, $order);
    }

    /**
     * @return array{0: Data, 1: object, 2: Order, 3: object}
     */
    private function recurring(): array
    {
        $helper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->onlyMethods([
            'getLanguage', 'getBaseCurrency', 'getFortCurrency', 'convertFortAmount', 'getMainConfigData',
            'calculateSignature', 'getGatewayUrl', 'getReturnUrl', 'callApi', 'log',
            'claimOrderForProcessing', 'releaseOrderProcessingClaim',
        ])->getMock();
        $helper->method('callApi')->willReturn(['response_code' => '14000', 'fort_id' => 'F1', 'merchant_reference' => '000000013']);
        $helper->method('getFortCurrency')->willReturn('EGP');

        $invoiceService = new class {
            public $calls = 0;
            public $fail = false;
            public function prepareInvoice($order)
            {
                $this->calls++;
                if ($this->fail) {
                    throw new \RuntimeException('invoice failed');
                }
                return null; // "Invoice not generated" branch: no persistence needed
            }
        };
        $orderSender = $this->getMockBuilder(\stdClass::class)->addMethods(['send'])->getMock();
        foreach (['invoiceService' => $invoiceService, '_orderSender' => $orderSender] as $name => $value) {
            $prop = new ReflectionProperty(Data::class, $name);
            $prop->setAccessible(true);
            $prop->setValue($helper, $value);
        }

        $payment = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)->disableOriginalConstructor()
            ->onlyMethods(['getMethod', 'setTransactionId', 'setIsTransactionClosed', 'setAdditionalInformation', 'setAdditionalData', 'save'])->getMock();
        $payment->method('getMethod')->willReturn(\Amazonpaymentservices\Fort\Model\Method\Vault::CODE);
        foreach (['setTransactionId', 'setIsTransactionClosed', 'setAdditionalInformation', 'setAdditionalData'] as $m) {
            $payment->method($m)->willReturnSelf();
        }
        $currency = new class {
            public function getCurrencyCode()
            {
                return 'EGP';
            }
        };
        $order = $this->getMockBuilder(Order::class)->disableOriginalConstructor()
            ->onlyMethods(['getPayment', 'getRealOrderId', 'getOrderCurrency', 'getRemoteIp', 'getCustomerEmail', 'getId', 'getData', 'getState', 'getStatus'])
            ->getMock();
        $order->method('getPayment')->willReturn($payment);
        $order->method('getRealOrderId')->willReturn('000000013');
        $order->method('getOrderCurrency')->willReturn($currency);
        $order->method('getCustomerEmail')->willReturn('buyer@example.test');
        $order->method('getData')->willReturn([]);
        $order->method('getState')->willReturn(Order::STATE_NEW);
        $order->method('getStatus')->willReturn('pending');
        return [$helper, $invoiceService, $order, $orderSender];
    }

    public function testRecurringPaymentDoesNotInvoiceWhenAnotherWriterHoldsTheClaim(): void
    {
        [$helper, $invoiceService, $order] = $this->recurring();
        $helper->method('claimOrderForProcessing')->willReturn(false);
        $helper->expects($this->never())->method('releaseOrderProcessingClaim');
        $helper->apsSubscriptionPaymentApi($order, 'tok', $order);
        $this->assertSame(0, $invoiceService->calls);
    }

    public function testRecurringPaymentInvoicesWhenItWinsTheClaim(): void
    {
        [$helper, $invoiceService, $order] = $this->recurring();
        $helper->method('claimOrderForProcessing')->willReturn(true);
        $helper->apsSubscriptionPaymentApi($order, 'tok', $order);
        $this->assertSame(1, $invoiceService->calls);
    }

    public function testRecurringPaymentReleasesTheClaimWhenInvoicingFails(): void
    {
        [$helper, $invoiceService, $order] = $this->recurring();
        $invoiceService->fail = true;
        $helper->method('claimOrderForProcessing')->willReturn(true);
        $helper->expects($this->once())->method('releaseOrderProcessingClaim')->with($order, Order::STATE_NEW, 'pending');
        $this->assertFalse($helper->apsSubscriptionPaymentApi($order, 'tok', $order));
    }

    public function testValuReferenceLookupUsesTheIncrementIdWhenNoEntityIdIsGiven(): void
    {
        $where = [];
        $select = new class($where) {
            private $where;
            public function __construct(array &$where)
            {
                $this->where = &$where;
            }
            public function from()
            {
                return $this;
            }
            public function where($condition, $value)
            {
                $this->where[] = [$condition, $value];
                return $this;
            }
        };
        $connection = $this->createMock(\Magento\Framework\DB\Adapter\AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $helper = $this->getMockBuilder(Data::class)->disableOriginalConstructor()->onlyMethods(['fetchAllQuery'])->getMock();
        $helper->method('fetchAllQuery')->willReturn([['aps_valu_ref' => 'VALU-REF-1']]);
        $resource = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)->disableOriginalConstructor()
            ->onlyMethods(['getConnection', 'getTableName'])->getMock();
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);
        $prop = new ReflectionProperty(Data::class, '_connection');
        $prop->setAccessible(true);
        $prop->setValue($helper, $resource);

        $this->assertSame('VALU-REF-1', $helper->getApsValuRefFromOrderParams(null, '000000012'));
        $this->assertSame([['table.order_increment_id=?', '000000012']], $where);
    }

    public function testCronDoesNotInvoiceWhenAnotherWriterHoldsTheClaim(): void
    {
        [$cron, $helper, $order] = $this->cron();
        $helper->method('claimOrderForProcessing')->willReturn(false);
        $helper->expects($this->never())->method('handleSendingInvoice');
        $order->expects($this->never())->method('save');
        $this->runOrderUpdate($cron, $order);
    }

    public function testCronInvoicesOnceWhenItWinsTheClaim(): void
    {
        [$cron, $helper, $order] = $this->cron();
        $helper->method('claimOrderForProcessing')->willReturn(true);
        $helper->expects($this->once())->method('handleSendingInvoice');
        $helper->expects($this->never())->method('releaseOrderProcessingClaim');
        $this->runOrderUpdate($cron, $order);
    }

    public function testCronReleasesTheClaimToTheOriginalStatusOnFailure(): void
    {
        [$cron, $helper, $order] = $this->cron();
        $helper->method('claimOrderForProcessing')->willReturn(true);
        $helper->method('handleSendingInvoice')->willThrowException(new \RuntimeException('invoice failed'));
        $helper->expects($this->once())->method('releaseOrderProcessingClaim')->with($order, Order::STATE_NEW, 'pending');
        $this->expectException(\RuntimeException::class);
        $this->runOrderUpdate($cron, $order);
    }
}
}
