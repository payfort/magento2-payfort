<?php
/**
 * Admin capture/void: the order number sent to the gateway (and written to
 * capture history) must belong to the order whose amounts bound the capture.
 */
namespace {
    if (!function_exists('__')) {
        function __(...$args)
        {
            $text = array_shift($args);
            if (!empty($args) && is_array($args[0])) {
                $args = $args[0];
            }
            return new \Magento\Framework\Phrase($text, $args);
        }
    }
}

namespace Amazonpaymentservices\Fort\Model {
    // Magento generates this factory at compile time; declare a stand-in outside an installed store.
    if (!class_exists(PaymentcaptureFactory::class)) {
        class PaymentcaptureFactory
        {
            public function create(array $data = [])
            {
                return null;
            }
        }
    }
}

namespace Amazonpaymentservices\Fort\Test\Unit\Admin {

    use Amazonpaymentservices\Fort\Controller\Adminhtml\Payment\Capture;
    use Amazonpaymentservices\Fort\Helper\Data;
    use PHPUnit\Framework\TestCase;

    class CaptureOrderReferenceTest extends TestCase
    {
        /** @var object */
        private $json;

        private function controller(array $params, Data $helper, array $history = []): Capture
        {
            $request = $this->createMock(\Magento\Framework\App\RequestInterface::class);
            $request->method('getParams')->willReturn($params);
            $context = $this->getMockBuilder(\Magento\Backend\App\Action\Context::class)->disableOriginalConstructor()
                ->onlyMethods(['getRequest'])->getMock();
            $context->method('getRequest')->willReturn($request);

            $this->json = new class {
                public $data;
                public function setData($data)
                {
                    $this->data = $data;
                    return $this;
                }
            };
            $jsonFactory = $this->getMockBuilder(\Magento\Framework\Controller\Result\JsonFactory::class)
                ->disableOriginalConstructor()->onlyMethods(['create'])->getMock();
            $jsonFactory->method('create')->willReturn($this->json);

            $order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)->disableOriginalConstructor()
                ->onlyMethods(['getIncrementId', 'getGrandTotal'])->getMock();
            $order->method('getIncrementId')->willReturn('000000012');
            $order->method('getGrandTotal')->willReturn(100.0);
            $repository = $this->createMock(\Magento\Sales\Api\OrderRepositoryInterface::class);
            $repository->method('get')->willReturnCallback(function ($id) use ($order) {
                if ($id !== 12) {
                    throw new \Magento\Framework\Exception\NoSuchEntityException();
                }
                return $order;
            });

            $collection = new class($history) implements \IteratorAggregate {
                private $items;
                public function __construct(array $items)
                {
                    $this->items = $items;
                }
                public function addFieldToFilter()
                {
                    return $this;
                }
                public function getIterator(): \ArrayIterator
                {
                    return new \ArrayIterator($this->items);
                }
            };
            $model = new class($collection) {
                private $collection;
                public function __construct($collection)
                {
                    $this->collection = $collection;
                }
                public function getCollection()
                {
                    return $this->collection;
                }
            };
            $captureFactory = $this->getMockBuilder(\Amazonpaymentservices\Fort\Model\PaymentcaptureFactory::class)
                ->disableOriginalConstructor()->onlyMethods(['create'])->getMock();
            $captureFactory->method('create')->willReturn($model);

            return new Capture($context, $jsonFactory, $helper, $captureFactory, $repository);
        }

        private function helper(): Data
        {
            return $this->getMockBuilder(Data::class)->disableOriginalConstructor()
                ->onlyMethods(['capturePayment', 'voidPayment', 'log'])->getMock();
        }

        /**
         * @dataProvider mismatchedReferences
         */
        public function testMismatchedOrderNumberIsRejectedBeforeGateway(string $type, $orderNumber): void
        {
            $helper = $this->helper();
            $helper->expects($this->never())->method('capturePayment');
            $helper->expects($this->never())->method('voidPayment');

            $this->controller(['paymentType' => $type, 'orderId' => '12', 'orderNumber' => $orderNumber, 'amount' => '10'], $helper)
                ->execute();

            $this->assertSame('', $this->json->data['data']['response_code']);
            $this->assertSame('Order reference does not match the order.', $this->json->data['data']['response_message']);
        }

        public function mismatchedReferences(): array
        {
            return [
                'capture other order' => ['capture', '000000099'],
                'void other order' => ['void', '000000099'],
                'array injection' => ['capture', ['000000012']],
            ];
        }

        public function testUnknownOrderIdIsRejectedBeforeGateway(): void
        {
            $helper = $this->helper();
            $helper->expects($this->never())->method('voidPayment');
            $this->controller(['paymentType' => 'void', 'orderId' => '77', 'orderNumber' => '000000012'], $helper)->execute();
            $this->assertSame('Order could not be loaded.', $this->json->data['data']['response_message']);
        }

        public function testMatchingCaptureStillEnforcesRemainingAmount(): void
        {
            $helper = $this->helper();
            $helper->expects($this->never())->method('capturePayment');
            $history = [new \Magento\Framework\DataObject(['payment_type' => 'capture', 'amount' => '95'])];
            $this->controller(['paymentType' => 'capture', 'orderId' => '12', 'orderNumber' => '000000012', 'amount' => '10'], $helper, $history)
                ->execute();
            $this->assertSame(
                'Capture amount cannot be greater than the remaining authorized amount.',
                $this->json->data['data']['response_message']
            );
        }

        public function testMatchingCaptureReachesGatewayAndRecordsSameOrder(): void
        {
            $helper = $this->helper();
            $helper->expects($this->once())->method('capturePayment')
                ->willReturn(['response_code' => Data::PAYMENT_METHOD_CAPTURE_STATUS]);
            $this->controller(['paymentType' => 'capture', 'orderId' => '12', 'orderNumber' => '000000012', 'amount' => '10'], $helper)
                ->execute();
            $this->assertSame('000000012', $this->json->data['payment']['order_number']);
        }

        public function testMatchingVoidReachesGateway(): void
        {
            $helper = $this->helper();
            $helper->expects($this->once())->method('voidPayment')
                ->willReturn(['response_code' => Data::PAYMENT_METHOD_VOID_STATUS]);
            // The admin form always posts amount; it is empty for void.
            $this->controller(['paymentType' => 'void', 'orderId' => '12', 'orderNumber' => '000000012', 'amount' => ''], $helper)->execute();
            $this->assertSame('void', $this->json->data['payment']['payment_type']);
        }
    }
}
