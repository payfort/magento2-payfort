<?php

namespace Amazonpaymentservices\Fort\Controller\Payment;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Filesystem\DirectoryList as FileSystem;

class GetAppleValidation extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface, HttpGetActionInterface, HttpPostActionInterface
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
     * @var Magento\Framework\Filesystem\File\ReadFactory
     */
    protected $_driver;

    /**
     * @param \Magento\Framework\App\Action\Context $context,
     * @param \Magento\Checkout\Model\Session $checkoutSession,
     * @param \Magento\Sales\Model\Order\Config $orderConfig,
     * @param \Amazonpaymentservices\Fort\Model\Payment $apsModel,
     * @param \Amazonpaymentservices\Fort\Helper\Data $helperFort
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order\Config $orderConfig,
        \Amazonpaymentservices\Fort\Model\Payment $apsModel,
        \Amazonpaymentservices\Fort\Helper\Data $helperFort,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        FileSystem $fileSystem,
        \Magento\Framework\Filesystem\File\ReadFactory $driver
    ) {
        parent::__construct($context);
        $this->_checkoutSession = $checkoutSession;
        $this->_orderConfig = $orderConfig;
        $this->_isScopePrivate = true;
        $this->_helper = $helperFort;
        $this->_apsModel = $apsModel;
        $this->_resultJsonFactory  = $resultJsonFactory;
        $this->_filesystem = $fileSystem;
        $this->_driver = $driver;
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

        $apple_url = $this->validateAppleUrl($responseParams['valURL'] ?? '');

        $mediapath = $this->_filesystem->getPath('media');
        $certificate_key =  $mediapath.'/aps/certificate_keys/'.$this->_helper->getConfig('payment/aps_apple/apple_key_pem');
        $certificate_path = $mediapath.'/aps/certificate_keys/'.$this->_helper->getConfig('payment/aps_apple/apple_certificate_pem');
        $read = $this->_driver->create($certificate_path, \Magento\Framework\Filesystem\DriverPool::FILE);
        $fileData = $read->readAll();
        $merchantidentifier = openssl_x509_parse($fileData)['subject']['UID'];
        $certificate_pass = $this->_helper->getConfig('payment/aps_apple/certificate_key');
        $domainName = $this->_helper->getConfig('payment/aps_apple/apple_domain_name');
        $displayName = $this->_helper->getConfig('payment/aps_apple/apple_display_name');

        $data = json_decode('{"merchantIdentifier":"'.$merchantidentifier.'", "domainName":"'.$domainName.'", "displayName":"'.$displayName.'"}');

        $result = $this->_helper->callApi($data, $apple_url, $certificate_key, $certificate_path, $certificate_pass);

        $jsonResult = $this->_resultJsonFactory->create();
        $jsonResult->setData($result);
        return $jsonResult;
    }

    private function validateAppleUrl($apple_url)
    {
        $apple_url = filter_var($apple_url, FILTER_SANITIZE_URL);

        if ( empty( $apple_url ) ) {
            throw new \Exception( 'Apple pay url is missing' );
        }
        if ( ! filter_var( $apple_url, FILTER_VALIDATE_URL ) ) {
            throw new \Exception( 'Apple pay url is invalid' );
        }

        $matched_apple = preg_match('/^https\:\/\/[^\.\/]+\.apple\.com\//', $apple_url);
        if ( ! $matched_apple ) {
            throw new \Exception( 'Apple pay url is invalid' );
        }

        return $apple_url;
    }
}
