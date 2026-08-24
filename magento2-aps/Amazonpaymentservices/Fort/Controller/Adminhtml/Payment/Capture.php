<?php
namespace Amazonpaymentservices\Fort\Controller\Adminhtml\Payment;
 
use Amazonpaymentservices\Fort\Model\PaymentcaptureFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Amazonpaymentservices\Fort\Helper\Data;
 
class Capture extends Action
{
    const ADMIN_RESOURCE = 'Amazonpaymentservices_Fort::capture';

    protected $_paymentCaptureFactory;

    protected $resultJsonFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Data $helper
     * @param PaymentcaptureFactory $paymentCaptureFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Data $helper,
        \Amazonpaymentservices\Fort\Model\PaymentcaptureFactory $paymentCaptureFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    ) {
        $this->_paymentCaptureFactory = $paymentCaptureFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;
        parent::__construct($context);
    }

    /**
     * Collect relations data
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $responseParams = $this->getRequest()->getParams();
        $data['postParams'] = $responseParams;

        if (($responseParams['paymentType'] ?? '') == 'capture') {
            $captureError = $this->validateCaptureAmount($responseParams);
            if ($captureError !== null) {
                return $this->resultJsonFactory->create()->setData([
                    'postParams' => $responseParams,
                    'data' => [
                        'response_code' => '',
                        'response_message' => $captureError,
                    ],
                ]);
            }
        }

        if ($responseParams['paymentType'] == 'capture') {
            $apsResponse = $this->helper->capturePayment($responseParams);
            if (!empty($apsResponse['response_code']) && $apsResponse['response_code'] == \Amazonpaymentservices\Fort\Helper\Data::PAYMENT_METHOD_CAPTURE_STATUS) {
                //$model = $this->_paymentCaptureFactory->create();
                $saveData['payment_type'] = 'capture';
                $saveData['order_number'] = $responseParams['orderNumber'];
                $saveData['amount'] = $responseParams['amount'];
                $saveData['added_date'] = date('Y-m-d H:i:s');
                $data['payment'] = $saveData;
            }
            $data['data'] = $apsResponse;
        } else {
            $apsResponse = $this->helper->voidPayment($responseParams);
            if (!empty($apsResponse['response_code']) && $apsResponse['response_code'] == \Amazonpaymentservices\Fort\Helper\Data::PAYMENT_METHOD_VOID_STATUS) {
                //$model = $this->_paymentCaptureFactory->create();
                $saveData['payment_type'] = 'void';
                $saveData['order_number'] = $responseParams['orderNumber'];
                $saveData['amount'] = $responseParams['amount'];
                $saveData['added_date'] = date('Y-m-d H:i:s');
                $data['payment'] = $saveData;
            }
            $data['data'] = $apsResponse;
        }

        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();

        return $result->setData($data);
    }

    /**
     * Validate the requested capture amount against the remaining authorized amount.
     *
     * @param array $responseParams
     * @return string|null Error message, or null when the amount is acceptable.
     */
    private function validateCaptureAmount(array $responseParams)
    {
        $amount = $responseParams['amount'] ?? null;

        if ($amount === null || $amount === '' || !is_numeric($amount)) {
            return (string)__('Capture amount is not valid.');
        }

        $amount = (float)$amount;
        if ($amount <= 0) {
            return (string)__('Capture amount must be greater than zero.');
        }

        try {
            $order = $this->orderRepository->get((int)($responseParams['orderId'] ?? 0));
        } catch (\Exception $e) {
            return (string)__('Order could not be loaded.');
        }

        $alreadyCaptured = 0;
        $collection = $this->_paymentCaptureFactory->create()
            ->getCollection()
            ->addFieldToFilter('order_number', ['eq' => $order->getIncrementId()]);
        foreach ($collection as $item) {
            if ($item->getData('payment_type') === 'void') {
                return (string)__('Capture is not allowed: the authorization has already been voided.');
            }
            $alreadyCaptured += (float)$item->getData('amount');
        }

        $capturable = (float)$order->getGrandTotal() - $alreadyCaptured;
        if ($amount > ($capturable + 0.0001)) {
            $this->helper->log(sprintf(
                'Capture rejected for order %s: requested %s exceeds remaining authorized amount %s.',
                $order->getIncrementId(),
                $amount,
                $capturable
            ));
            return (string)__('Capture amount cannot be greater than the remaining authorized amount.');
        }

        return null;
    }
}
