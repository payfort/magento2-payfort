<?php
/**
 * Amazonpaymentservices Payment Helper
 * php version 7.3.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
namespace Amazonpaymentservices\Fort\Helper;

use Magento\Framework\Message\Manager;
use Amazonpaymentservices\Fort\Model\Payment;
use Amazonpaymentservices\Fort\Helper\Data as apsHelper;

/**
 * Amazonpaymentservices Payment Helper
 * php version 7.3.*
 *
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class Aps extends \Magento\Payment\Helper\Data
{
    
    /**
     * @var apsHelper
     */
    protected $apsHelper;

    /**
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var File System
     */
    protected $_filesystem;

    /**
     * @var \Magento\Framework\Filesystem\File\ReadFactory
     */
    protected $_driver;
          
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Payment\Model\Method\Factory $paymentMethodFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Payment\Model\Config $paymentConfig,
        \Magento\Framework\App\Config\Initial $initialConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Filesystem\DirectoryList $fileSystem,
        \Magento\Framework\Filesystem\File\ReadFactory $driver,
        apsHelper $apsHelper
    ) {
        parent::__construct($context, $layoutFactory, $paymentMethodFactory, $appEmulation, $paymentConfig, $initialConfig);
        $this->_logger = $context->getLogger();
        $this->_objectManager = $objectManager;
        $this->_filesystem = $fileSystem;
        $this->apsHelper = $apsHelper;
        $this->_driver = $driver;
    }

    public function getAppleCertificatePath()
    {
        $mediapath = $this->_filesystem->getPath('media');
        $certificate_path = $mediapath.'/aps/certificate_keys/'.$this->apsHelper->getConfig('payment/aps_apple/apple_certificate_pem');
        $read = $this->_driver->create($certificate_path, \Magento\Framework\Filesystem\DriverPool::FILE);
        $merchantidentifier = '';
        if ($read) {
            $content = $read->readAll();
            $merchantidentifier = openssl_x509_parse($content)['subject']['UID'];
        }
        return $merchantidentifier;
    }
}
