<?php

namespace Amazonpaymentservices\Fort\Controller\Product;

use Amazonpaymentservices\Fort\Helper\Data;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Currency;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Filter\LocalizedToNormalized;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Laminas\Uri\Uri;

class AddProductToCart extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Helper class property
     *
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;
    

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
     * @var \Laminas\Uri\Uri
     */
    private $laminasUri;

    /**
     * Adding a product to cart
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param Data $helperFort
     * @param Cart $cart
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param ManagerInterface $eventManager
     * @param \Magento\Directory\Model\Currency $modelCurrency
     * @param ResolverInterface $localeResolverInterface
     * @param Uri $laminasUri
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Amazonpaymentservices\Fort\Helper\Data $helperFort,
        \Magento\Checkout\Model\Cart $cart,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Directory\Model\Currency $modelCurrency,
        \Magento\Framework\Locale\ResolverInterface $localeResolverInterface,
        \Laminas\Uri\Uri $laminasUri
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helperFort;
        $this->cart = $cart;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->eventManager = $eventManager;
        $this->modelCurrency = $modelCurrency;
        $this->localeResolverInterface = $localeResolverInterface;
        $this->laminasUri = $laminasUri;
    }

    /**
     * @param RequestInterface $request
     *
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
            return null;
    }

    /**
     * @param RequestInterface $request
     *
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Executes the command
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $responseParams = $this->getRequest()->getParams();
        $this->laminasUri->setQuery($responseParams['request']);
        $request = $this->laminasUri->getQueryAsArray();
        
        $productId = $request['product'];
        $related = $request['related_product'];
        $data = [];

        if (isset($request['qty'])) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $filter = new LocalizedToNormalized(
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
        $data['data']['totalPriceUnformatted'] = (string)$data['data']['totalPrice'];
        $data['data']['totalPrice'] =
            $this->modelCurrency->format(
                $data['data']['totalPrice'],
                [
                    'display'=> Currency::NO_SYMBOL
                ],
                false
            );
        $data['data']['totalPrice'] = str_replace(",", "", $data['data']['totalPrice']);
        $data['data']['shippingAmount']  = 0;
        $data['data']['shippingAmountUnformatted']  = (string)$data['data']['shippingAmount'];
        $data['data']['totalTax']  = 0;
        $data['data']['totalTaxUnformatted']  = (string)$data['data']['totalTax'];
        $data['data']['discountAmount']  = $quote->getSubtotal() - $quote->getSubtotalWithDiscount();
        $data['data']['discountAmountUnformatted']  = (string)$data['data']['discountAmount'];
        $data['data']['discountAmount'] =
            $this->modelCurrency->format(
                $data['data']['discountAmount'],
                [
                    'display'=> Currency::NO_SYMBOL
                ],
                false
            );
        $data['data']['discountAmount'] = str_replace(",", "", $data['data']['discountAmount']);

        $data['data']['total']  = $quote->getGrandTotal();
        $data['data']['totalUnformatted']  = (string)$data['data']['total'];
        $data['data']['total'] =
            $this->modelCurrency->format(
                $data['data']['total'],
                [
                    'display'=> Currency::NO_SYMBOL
                ],
                false
            );

        $data['data']['total'] = str_replace(",", "", $data['data']['total']);

        $data['status'] = 'success';
        $this->_helper->log('Apple Json : '.json_encode($data));
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($data);
        return $resultJson;
    }
    public function getCacheLifetime()
    {
        return null;
    }
}
