<?php
namespace Amazonpaymentservices\Fort\Block\Adminhtml\Orderedit\Tab;
 
class View extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_orderRepository;

    protected $_paymentCaptureFactory;

    protected $_template = 'tab/view/myorderinfo.phtml';
 
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Amazonpaymentservices\Fort\Model\PaymentcaptureFactory $paymentCaptureFactory,
        array $data = []
    ) {
        $this->_paymentCaptureFactory = $paymentCaptureFactory;
        $this->_orderRepository = $orderRepository;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }
    public function getOrderId()
    {
        return $this->getOrder()->getEntityId();
    }
    public function getOrderIncrementId()
    {
        return $this->getOrder()->getIncrementId();
    }
    public function getTabLabel()
    {
        return __('Capture/Void');
    }
    public function getTabTitle()
    {
        return __('Capture/Void');
    }
    public function canShowTab()
    {
        return true;
    }
    public function isHidden()
    {
        return false;
    }

    public function getAjaxUrl()
    {
        return $this->getUrl('amazonpaymentservicesfort/payment/capture');
    }

    public function getReturnUrl($path)
    {
        return $this->_storeManager->getStore()->getBaseUrl().$path;
    }

    public function orderPayments()
    {
        $order = $this->_orderRepository->get($this->getOrderId());
        $payment = $order->getPayment();
        $method = $payment->getMethodInstance();
        $data['title'] = $method->getTitle(); // Cash On Delivery
        $data['code'] = $method->getCode(); // cashondelivery
        if (in_array($order->getPayment()->getMethod(), \Amazonpaymentservices\Fort\Helper\Data::PAYMENT_METHOD)) {
            $data['amountPaid'] = $payment->getAmountPaid();
            $additionalData = $payment->getAdditionalData();
            $data['additionalData'] = [];
            if (!empty($additionalData)) {
                $data['additionalData'] = json_decode($additionalData, true);
            }
        } else {
            $data['amountPaid'] = 0;
            $data['additionalData'] = [];
        }
        return $data;
    }

    public function getPaymentData()
    {
        $dataArr = [];
        $post = $this->_paymentCaptureFactory->create();
        $collection = $post->getCollection()->addFieldToFilter('order_number', ['eq' => $this->getOrderIncrementId()]);
        foreach ($collection as $item) {
            $dataArr[] = $item->getData();
        }
        return $dataArr;
    }
}
