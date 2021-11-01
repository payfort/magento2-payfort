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

class AddProductToCart extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    private $localeResolverInterface;

    /**
     * @var \Zend\Uri\Uri
     */
    private $zendUri;
    
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
        \Magento\Directory\Model\Currency $modelCurrency,
        \Magento\Framework\Locale\ResolverInterface $localeResolverInterface,
        \Zend\Uri\Uri $zendUri
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
        $this->localeResolverInterface = $localeResolverInterface;
        $this->zendUri = $zendUri;
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
        $responseParams = $this->getRequest()->getParams();
        $this->zendUri->setQuery($responseParams['request']);
        $request = $this->zendUri->getQueryAsArray();
        
        $productId = $request['product'];
        $related = $request['related_product'];
        $data = [];

        if (isset($request['qty'])) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $filter = new \Zend_Filter_LocalizedToNormalized(
                ['locale' => $this->localeResolverInterface->getLocale()]
            );
            $request['qty'] = $filter->filter($request['qty']);
        }

        $quote = $this->cart->getQuote();

        $storeId = $this->storeManager->getStore()->getId();
        $product = $this->productRepository->getById($productId, false, $storeId);

        // Check is update required
        $isUpdated = false;
        foreach ($quote->getAllItems() as $item) {
            if ($item->getProductId() == $productId) {
                $item = $this->cart->updateItem($item->getId(), $request);
                if ($item->getHasError()) {
                    throw new LocalizedException(__($item->getMessage()));
                }

                $isUpdated = true;
                break;
            }
        }

        // Add Product to Cart
        if (!$isUpdated) {
            $item = $this->cart->addProduct($product, $request);
            if ($item->getHasError()) {
                throw new LocalizedException(__($item->getMessage()));
            }

            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }
        }

        $this->cart->save();

        // Update totals
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();
        $quote->save();
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
