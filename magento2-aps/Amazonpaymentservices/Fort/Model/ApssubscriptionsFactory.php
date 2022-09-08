<?php
namespace Amazonpaymentservices\Fort\Model;

class ApssubscriptionsFactory extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Create new APS Subscriptions model
     *
     * @param array $arguments
     * @return \Amazonpaymentservices\Fort\Model\Apssubscriptions
     */
    public function create(array $arguments = [])
    {
        return $this->_objectManager->create('Amazonpaymentservices\Fort\Model\Apssubscriptions', $arguments, false);
    }
}
