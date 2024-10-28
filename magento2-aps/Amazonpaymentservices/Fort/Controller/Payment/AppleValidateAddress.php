<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Currency;
use Magento\Sales\Model\Order;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Filesystem\DirectoryList as FileSystem;

class AppleValidateAddress extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     *
     * @var \Amazonpaymentservices\Fort\Model\Payment
     */
    protected $_apsModel;

    /**
     * Helper
     *
     * @var \Amazonpaymentservices\Fort\Helper\Data
     */
    protected $_helper;

    /**
     * @var
     */
    protected $_resultJsonFactory;

    /**
     * @var File System
     */
    protected $_filesystem;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_cart;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;

    /**
     * @var \Magento\Directory\Model\CountryFactory
     */
    protected $countryFactory;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Quote\Api\Data\EstimateAddressInterfaceFactory
     */
    protected $estimatedAddressFactory;

    /**
     * @var \Magento\Quote\Api\ShippingMethodManagementInterface
     */
    protected $shippingMethodManager;

    /**
     * @var \Magento\Directory\Model\Currency
     */
    protected $modelCurrency;

    /**
     * @var \Magento\Quote\Model\Quote\Address\Rate
     */
    protected $shippingRate;
    /**
     * @param \Magento\Framework\App\Action\Context $context,
     * @param \Magento\Checkout\Model\Session $checkoutSession,
     * @param \Magento\Sales\Model\Order\Config $orderConfig,
     * @param \Amazonpaymentservices\Fort\Model\Payment $apsModel,
     * @param \Amazonpaymentservices\Fort\Helper\Data $helperFort,
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Amazonpaymentservices\Fort\Model\Payment $apsModel,
        \Amazonpaymentservices\Fort\Helper\Data $helperFort,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        FileSystem $fileSystem,
        \Magento\Catalog\Model\Product $product,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Directory\Model\Country $countryFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Quote\Api\Data\EstimateAddressInterfaceFactory $estimatedAddressFactory,
        \Magento\Quote\Api\ShippingMethodManagementInterface $shippingMethodManager,
        \Magento\Directory\Model\Currency $modelCurrency,
        \Magento\Quote\Model\Quote\Address\Rate $shippingRate
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_helper = $helperFort;
        $this->_apsModel = $apsModel;
        $this->_resultJsonFactory  = $resultJsonFactory;
        $this->_filesystem = $fileSystem;
        $this->_product = $product;
        $this->_cart = $cart;
        $this->countryFactory = $countryFactory;
        $this->quoteRepository = $quoteRepository;
        $this->estimatedAddressFactory = $estimatedAddressFactory;
        $this->shippingMethodManager = $shippingMethodManager;
        $this->modelCurrency = $modelCurrency;
        $this->shippingRate = $shippingRate;
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
        $addressData = $responseParams['addressObject'];
        $countryCode = null;

        $this->_helper->log('Apple Address:'.json_encode($addressData));
        $result = [];
        if (isset($addressData['countryCode']) && $this->_helper->getConfig('payment/aps_apple/allowspecific') != 0) {
            $countryCode = $this->_helper->getConfig('payment/aps_apple/specificcountry');
            $countryCode = explode(',', $countryCode);
            $this->_helper->log('Apple Config: Country');
            if (in_array($addressData['countryCode'], $countryCode)) {
                $countryCode = $addressData['countryCode'];
                $result = $this->getShippingRates($addressData);
            } else {
                $dataArr['error_msg'] = __('Country not allowed for shipping');
            }
        } elseif (isset($addressData['countryCode'])) {
            $result = $this->getShippingRates($addressData);
        }
        $quote = $this->_cart->getQuote();

        if($countryCode) {
            $quote->getShippingAddress()->setRegionCode($countryCode);
        }
        if(isset($addressData['postalCode'])) {
            $quote->getShippingAddress()->setPostcode($addressData['postalCode']);
        }

        if(!empty($result[0]['id'])) {
            $quote->getShippingAddress()->setShippingMethod($result[0]['id']);
            $quote->save();
        }
        $quote = $this->_cart->getQuote();

        $quote->getShippingAddress()->setCountryId($addressData['countryCode']);
        $quote->save();
        $quote = $this->_cart->getQuote();
        $quote->setTotalsCollectedFlag(false);
        $quote->collectTotals();

        $dataArr['taxes'] = $quote->getShippingAddress()->getData('tax_amount');
        $dataArr['taxes'] = str_replace(",", "", $dataArr['taxes']);

        $dataArr['taxes'] =
            $this->modelCurrency->format(
                $dataArr['taxes'],
                [
                    'display'=> Currency::NO_SYMBOL
                ],
                false
            );
        $dataArr['taxes'] = str_replace(",", "", $dataArr['taxes']);

        if(isset($result[0]['amount'])) {
            $result[0]['amount'] = $quote->getShippingAddress()->getData('shipping_amount');
            $result[0]['amount'] = str_replace(",", "", $result[0]['amount']);
            $result[0]['amount'] =
                $this->modelCurrency->format(
                    $result[0]['amount'],
                    [
                        'display'=> Currency::NO_SYMBOL
                    ],
                    false
                );
            $result[0]['amount'] = str_replace(",", "", $result[0]['amount']);
        }

        $dataArr['data'] = $result;
        $dataArr['status'] = 'success';

        $jsonResult = $this->_resultJsonFactory->create();
        $jsonResult->setData($dataArr);
        return $jsonResult;
    }

    private function getShippingRates($addressData)
    {
        $quote = $this->_cart->getQuote();
        $result = [];
        if (!$quote->isVirtual()) {
            $shippingAddress = $this->getShippingAddress($addressData);
            $this->_helper->log('Address1');
            $this->_helper->log(json_encode($shippingAddress));
            $quote->getShippingAddress()
                ->addData($shippingAddress)
                ->save();
            $quoteId = $quote->getId();
            $address = $quote->getShippingAddress();

            $estimatedAddress = $this->estimatedAddressFactory->create();
            $estimatedAddress->setCountryId($address->getCountryId());
            $estimatedAddress->setPostcode($address->getPostcode());
            $estimatedAddress->setRegion((string)$address->getRegion());
            $estimatedAddress->setRegionId($address->getRegionId());

            $rates = $this->shippingMethodManager->estimateByAddress($quote->getId(), $estimatedAddress);
            foreach ($rates as $rate) {
                if ($rate->getErrorMessage()) {
                    continue;
                }

                $result[] = [
                    'id' => $rate->getCarrierCode() . '_' . $rate->getMethodCode(),
                    'label' => implode(' - ', [$rate->getCarrierTitle(), $rate->getMethodTitle()]),
                    'amount' => $rate->getPriceExclTax()
                ];
            }

            $this->shippingRate
                ->setCode($result[0]['id'])
                ->getPrice(1);
            $shippingAddress = $quote->getShippingAddress();

            $shippingAddress->setCollectShippingRates(true)
                ->collectShippingRates()
                ->setShippingMethod($result[0]['id']);
            $quote->getShippingAddress()->addShippingRate($this->shippingRate);

            $this->quoteRepository->save($quote);

            // Collect Totals & Save Quote
            $quote->collectTotals();

            $this->quoteRepository->save($quote);
        }

        return $result;
    }

    /**
     * Get Shipping Address
     * @param $address
     *
     * @return array
     */
    public function getShippingAddress($address)
    {
        $firstName = '';
        $lastName = '';

        $regionId = $this->getRegionIdBy($address['countryCode']);

        return [
            'firstname' => $firstName,
            'lastname' => $lastName,
            'company' => (!empty($address['organization']) ? $address['organization'] : ''),
            'email' => '',
            'street' => (empty($address['addressLine']) ? ["Unspecified Street"] : $address['addressLine']),
            'city' => $address['locality'],
            'region_id' => $regionId,
            'region' => $address['administrativeArea'],
            'postcode' => $address['postalCode'],
            'country_id' => $address['countryCode'],
            'telephone' => (!empty($address['phone']) ? $address['phone'] : ''),
            'fax' => ''
        ];
    }

    public function getRegionIdBy($countryCode)
    {
        $country = $this->countryFactory->loadByCode($countryCode);

        if (empty($country)) {
            return '';
        }
        $regions = $country->getRegions();

        foreach ($regions as $region) {
            return $region->getId();
        }

        return null;
    }
    public function getCacheLifetime()
    {
        return null;
    }
}
