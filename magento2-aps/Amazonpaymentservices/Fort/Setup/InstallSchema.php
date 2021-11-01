<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Amazonpaymentservices\Fort\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $conn = $setup->getConnection();
        $tableName = $setup->getTable('aps_capture_payment');
        if ($conn->isTableExists($tableName) != true) {
            $table = $conn->newTable($tableName)
                            ->addColumn(
                                'id',
                                Table::TYPE_INTEGER,
                                null,
                                ['identity'=>true,'unsigned'=>true,'nullable'=>false,'primary'=>true]
                            )
                            ->addColumn(
                                'payment_type',
                                Table::TYPE_TEXT,
                                255,
                                ['nullable'=>false,'default'=>'']
                            )
                            ->addColumn(
                                'order_number',
                                Table::TYPE_TEXT,
                                100,
                                ['nullbale'=>false,'default'=>0]
                            )
                            ->addColumn(
                                'amount',
                                Table::TYPE_DECIMAL,
                                '10,2',
                                ['nullbale'=>false,'default'=>0]
                            )
                            ->addColumn(
                                'added_date',
                                Table::TYPE_TEXT,
                                50,
                                ['nullbale'=>false,'default'=>'']
                            )
                            ->setOption('charset', 'utf8');
            $conn->createTable($table);
        }
        $setup->endSetup();
    }
}
