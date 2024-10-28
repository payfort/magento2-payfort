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
     * @param Context $context ,
     * @param Session $checkoutSession ,
     * @param Data $helperFort
     * @param Cart $cart
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param ManagerInterface $eventManager
     * @param \Magento\Directory\Model\Currency $modelCurrency
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Amazonpaymentservices\Fort\Helper\Data $helperFort,
        \Magento\Checkout\Model\Cart $cart,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Directory\Model\Currency $modelCurrency
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_helper = $helperFort;
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
