<?php

namespace Amazonpaymentservices\Fort\Controller\Product;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;

class GetQuoteData extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Helper
     *
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;
    
    /**
     * JSON Helper
     *
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_jsonFactory;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $cart;
    
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    private $eventManager;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    private $modelCurrency;
    
    /**
     * @param \Magento\Framework\App\Action\Context $context,
     * @param \Magento\Checkout\Model\Session $checkoutSession,
     * @param \Amazonpaymentservices\Fort\Helper\Data $helperFort,
     * @param \Magento\Framework\Controller\Result\JsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Amazonpaymentservices\Fort\Helper\Data $helperFort,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Checkout\Model\Cart $cart,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Directory\Model\Currency $modelCurrency
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_isScopePrivate = true;
        $this->_helper = $helperFort;
        $this->_jsonHelper = $jsonFactory;
        $this->cart = $cart;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->eventManager = $eventManager;
        $this->modelCurrency = $modelCurrency;
    }
    
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
            return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
    
    public function execute()
    {
        $quote = $this->cart->getQuote();
        $data['data']['totalPrice'] = 0;
        foreach ($quote->getAllItems() as $item) {
            $data['data']['totalPrice']  += $item->getRowTotal();

        }
        $data['data']['totalPrice'] =  $this->modelCurrency->format($data['data']['totalPrice'], ['display'=>\Zend_Currency::NO_SYMBOL], false);
        $data['data']['totalPrice'] = str_replace(",", "", $data['data']['totalPrice']);
        $data['data']['shippingAmount']  = 0;
        $data['data']['totalTax']  = 0;
        $data['data']['discountAmount']  = $quote->getSubtotal() - $quote->getSubtotalWithDiscount();
        $data['data']['discountAmount'] =  $this->modelCurrency->format($data['data']['discountAmount'], ['display'=>\Zend_Currency::NO_SYMBOL], false);
        $data['data']['discountAmount'] = str_replace(",", "", $data['data']['discountAmount']);

        $data['data']['total']  = $quote->getGrandTotal();
        $data['data']['total'] =  $this->modelCurrency->format($data['data']['total'], ['display'=>\Zend_Currency::NO_SYMBOL], false);
        $data['data']['total'] = str_replace(",", "", $data['data']['total']);

        $data['status'] = 'success';
        $this->_helper->log('Apple Json : '.json_encode($data));
        $resultJson = $this->_jsonHelper->create();
        return $resultJson->setData($data);
    }
}
