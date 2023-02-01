<?php

namespace Amazonpaymentservices\Fort\Block;

class Success extends \Magento\Checkout\Block\Onepage\Success
{
    /**
     * @var string
     */
    protected $_template = 'Amazonpaymentservices_Fort::order/success.phtml';
    public $order = null;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->httpContext = $httpContext;

        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);

        $this->pricingHelper = $pricingHelper;
        $this->checkoutHelper = $checkoutHelper;
        $this->order = $this->_checkoutSession->getLastRealOrder();
    }

    public function getBaseGrandTotal()
    {
        return $this->order->getBaseGrandTotal();
    }

    public function getMethod()
    {
        if ($this->order && $this->order->getPayment()) {
            return $this->order->getPayment()->getMethod();
        }

        return null;
    }

    public function isKnetPaymentMethod()
    {
        return $this->getMethod() == "aps_knet";
    }

    public function isValuPaymentMethod()
    {
        return $this->getMethod() == "aps_fort_valu";
    }

    public function getKnetParmeters()
    {
        $payment = $this->order->getPayment();
        $data = $payment->getAdditionalData();
        $knetData = json_decode($data, true);
        return $knetData;
    }

    public function getValuParmeters()
    {
        $payment = $this->order->getPayment();
        $data = $payment->getAdditionalData();
        $valuData = json_decode($data, true);
        return $valuData;
    }

    public function getOrderNumber()
    {
        return $this->order->getIncrementId();
    }
}
