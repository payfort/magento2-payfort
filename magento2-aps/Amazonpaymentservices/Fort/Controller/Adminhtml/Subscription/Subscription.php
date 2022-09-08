<?php
namespace Amazonpaymentservices\Fort\Controller\Adminhtml\Subscription;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Subscription extends \Magento\Backend\App\Action
{
    protected $_publicActions = ['subscription'];

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Resource Connection
     */
    protected $_connection;
 
    /**
     * @param Context $context
     * @param Data $helper
     * @param ResourceConnection $connect
     */
    public function __construct(
        Context $context,
        \Amazonpaymentservices\Fort\Helper\Data $helper,
        \Magento\Framework\App\ResourceConnection $connect
    ) {
        $this->helper = $helper;
        $this->_connection = $connect;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->helper->log('Subscript status change call');
        $data['subid'] = $this->getRequest()->getParam('subid');
        $data['orderId'] = $this->getRequest()->getParam('orderid');
        $data['status'] = $this->getRequest()->getParam('status');
        
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            $connection = $this->_connection->getConnection();

            $date_now = date('Y-m-d H:i:s', strtotime('now'));

            $model = $this->_objectManager->get('Amazonpaymentservices\Fort\Model\ApssubscriptionsFactory')->create();
                
            $model->load($data['subid']);
            $model->setSubscriptionStatus($data['status']);

            if ($data['status'] == 1) {
                $query = $connection->select()->from(['table'=>'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_interval');
                $apsSubInterval = $connection->fetchRow($query);

                $query = $connection->select()->from(['table'=>'eav_attribute'], ['attribute_id'])->where('table.attribute_code=?', 'aps_sub_interval_count');
                $apsSubIntervalCount = $connection->fetchRow($query);

                /* @Subscription Interval */
                $query = $connection->select()->from(['table'=>'catalog_product_entity_varchar'], ['value'])->where('table.attribute_id=?', $apsSubInterval['attribute_id'])->where('table.entity_id=?', $model->getProductId());
                $prodApsSubInterval = $connection->fetchRow($query);

                /* @Subscription Interval Count*/
                $query = $connection->select()->from(['table'=>'catalog_product_entity_varchar'], ['value'])->where('table.attribute_id=?', $apsSubIntervalCount['attribute_id'])->where('table.entity_id=?', $model->getProductId());
                $prodApsSubIntervalCount = $connection->fetchRow($query);

                $date_now = date('Y-m-d H:i:s', strtotime('now'));
                $nextPaymentDate = date('Y-m-d', strtotime('+'.$prodApsSubIntervalCount['value'].' '.$prodApsSubInterval['value'], strtotime('now')));
                $model->setNextPaymentDate($nextPaymentDate);
            }
            $model->setUpdatedAt($date_now);
            
            if ($model->save()) {
                if($data['status'] == 1) {
                    $this->messageManager->addSuccess(__('Subscription status is changed successfully.'));
                } else {
                    $this->messageManager->addSuccess(__('Subscription status is changed successfully.'));
                }
            } else {
                $this->messageManager->addError(__('Subscription status change has been failed.'));
            }

            return $resultRedirect->setPath('sales/order/view', ['order_id' => $this->getRequest()->getParam('orderid')]);
        }
        return $resultRedirect->setPath('sales/order/');
    }
}
