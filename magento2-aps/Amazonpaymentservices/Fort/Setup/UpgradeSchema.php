<?php
/**
 * Amazonpaymentservices Installer upgrade schema
 * php version 7.3.*
 *
 * @category Amazonpaymentservices
 * @package  Amazonpaymentservices
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/

namespace Amazonpaymentservices\Fort\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use Zend_Db_Exception;

/**
 * Amazonpaymentservices Setup Upgrade Schema
 * php version 7.3.*
 * @author   Amazonpaymentservices <email@example.com>
 * @license  GNU / GPL v3
 * @version  GIT: @1.0.0@
 * @link     Amazonpaymentservices
 **/
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * @var QuoteResource
     */
    protected $quoteResource;

    /**
     * @var OrderResource
     */
    protected $orderResource;

    /**
     * UpgradeSchemaPlugin constructor.
     * @param QuoteResource $quoteResource
     * @param OrderResource $orderResource
     */
    public function __construct(QuoteResource $quoteResource, OrderResource $orderResource)
    {
        $this->quoteResource = $quoteResource;
        $this->orderResource = $orderResource;
    }

    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     *
     * @throws Zend_Db_Exception
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $connection = $setup->getConnection();

        $salesOrderConnection = $this->orderResource->getConnection();
        $salesOrderConnection->addColumn($setup->getTable('sales_order'), 'aps_valu_ref', [
            'type' => Table::TYPE_TEXT,
            'nullable' => true,
            'length' => 50,
            'default' => 0,
            'comment' => 'APS Valu Reference Number'
        ]);
        $salesOrderConnection->addColumn($setup->getTable('sales_order'), 'aps_params', [
            'type' => Table::TYPE_TEXT,
            'nullable' => true,
            'length' => 255,
            'default' => 0,
            'comment' => 'APS Valu & Install params'
        ]);

        $setup->endSetup();
    }
}
