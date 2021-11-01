<?php
namespace Amazonpaymentservices\Fort\Controller\Adminhtml\Payment;
 
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Amazonpaymentservices\Fort\Helper\Data;
 
class Capture extends Action
{
    protected $_paymentCaptureFactory;
 
    protected $resultJsonFactory;
 
    /**
     * @var Data
     */
    protected $helper;
 
    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Data $helper,
        \Amazonpaymentservices\Fort\Model\PaymentcaptureFactory $paymentCaptureFactory
    ) {
        $this->_paymentCaptureFactory = $paymentCaptureFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->helper = $helper;
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
}
