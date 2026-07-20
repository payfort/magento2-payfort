<?php
/**
 * Amazonpaymentservices Fort - Apple Pay certificate backend model
 * php version 8.2.*
 *
 * Stores the Apple Pay merchant certificate and private key files inside
 * the Magento ``var`` directory (not web-served) instead of ``pub/media``
 * (which is web-served for storefront assets). This prevents unauthorised
 * over-HTTP retrieval of the private key even if the filename is known
 * or guessable.
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices
 * @license  GNU / GPL v3
 **/
namespace Amazonpaymentservices\Fort\Model\Config\Backend;

use Magento\Framework\App\Filesystem\DirectoryList;

class AppleCertificateFile extends \Magento\Config\Model\Config\Backend\File
{
    /**
     * Override the destination directory used by the parent uploader so that
     * the uploaded pem files end up under ``<magento_root>/var/aps/certificate_keys/``
     * rather than ``<magento_root>/pub/media/aps/certificate_keys/``.
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface $requestData
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Config\Model\Config\Backend\File\RequestData\RequestDataInterface $requestData,
        \Magento\Framework\Filesystem $filesystem,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $requestData,
            $filesystem,
            $resource,
            $resourceCollection,
            $data
        );
        // Re-point the writable directory used by the parent uploader from
        // ``pub/media`` to ``var``. ``var`` is not exposed by the web
        // server in a default Magento deployment, so uploaded certificate
        // and key files cannot be fetched over HTTP.
        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
    }

    /**
     * Restrict uploaded certificate/key files to PEM.
     *
     * @return string[]
     */
    protected function _getAllowedExtensions()
    {
        return ['pem'];
    }
}
