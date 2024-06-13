<?php
namespace Amazonpaymentservices\Fort\Model;

class ApsorderparamsFactory extends \Magento\Framework\Model\AbstractModel
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
     * Create new APS order params model
     *
     * @param array $arguments
     * @return \Amazonpaymentservices\Fort\Model\Apsorderparams
     */
    public function create(array $arguments = [])
    {
        return $this->_objectManager->create('Amazonpaymentservices\Fort\Model\Apsorderparams', $arguments, false);
    }
}
