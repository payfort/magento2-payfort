<?php

namespace Amazonpaymentservices\Fort\Setup\Patch\Data;

use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Eav\Model\Entity\TypeFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;

class InitialInstall
    implements DataPatchInterface,
    PatchRevertableInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var \Magento\Catalog\Setup\CategorySetupFactory
     */
    private $_categorySetupFactory;

    /**
     * @var \Magento\Eav\Model\Entity\TypeFactory
     */
    private $_eavTypeFactory;

    /**
     * @var \Magento\Eav\Model\Entity\Attribute\SetFactory
     */
    private $_attributeSetFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory
     */
    private $_groupCollectionFactory;

    /**
     * @var \Magento\Eav\Setup\EavSetupFactory
     */
    private $_eavSetupFactory;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CategorySetupFactory $categorySetupFactory
     * @param TypeFactory $eavTypeFactory
     * @param SetFactory $attributeSetFactory
     * @param EavSetupFactory $eavSetupFactory
     * @param CollectionFactory $groupCollectionFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        \Magento\Catalog\Setup\CategorySetupFactory $categorySetupFactory,
        \Magento\Eav\Model\Entity\TypeFactory $eavTypeFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory,
        \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory
    ) {
        /**
         * If before, we pass $setup as argument in install/upgrade function, from now we start
         * inject it with DI. If you want to use setup, you can inject it, with the same way as here
         */
        $this->moduleDataSetup = $moduleDataSetup;
        $this->_categorySetupFactory = $categorySetupFactory;
        $this->_eavTypeFactory = $eavTypeFactory;
        $this->_attributeSetFactory = $attributeSetFactory;
        $this->_groupCollectionFactory = $groupCollectionFactory;
        $this->_eavSetupFactory = $eavSetupFactory;
    }

    /**
     * @inheritdoc
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $setup = $this->moduleDataSetup;
        $this->initSubscriptions($setup);
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [
        ];
    }

    /**
     * @inheritdoc
     */
    public function revert()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        //Here should go code that will revert all operations from `apply` method
        //Please note, that some operations, like removing data from column, that is in role of foreign key reference
        //is dangerous, because it can trigger ON DELETE statement
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        /**
         * This internal Magento method, that means that some patches with time can change their names,
         * but changing name should not affect installation process, that's why if we will change name of the patch
         * we will add alias here
         */
        return [];
    }

    /**
     * @inheritdoc
     */
    private function initSubscriptions($setup)
    {
        $groupName = 'Subscriptions by APS';

        $attributes = [
            'aps_sub_enabled' => [
                'type'                  => 'int',
                'label'                 => 'APS Subscription Enabled',
                'input'                 => 'boolean',
                'source'                => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'sort_order'            => 100,
                'global'                => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group'                 => $groupName,
                'is_used_in_grid'       => false,
                'is_visible_in_grid'    => false,
                'is_filterable_in_grid' => false,
                'used_for_promo_rules'  => true,
                'required'              => false
            ],
            'aps_sub_interval' => [
                'type'                  => 'varchar',
                'label'                 => 'Frequency',
                'input'                 => 'select',
                'source'                => 'Amazonpaymentservices\Fort\Model\Adminhtml\Source\BillingInterval',
                'sort_order'            => 110,
                'global'                => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group'                 => $groupName,
                'is_used_in_grid'       => false,
                'is_visible_in_grid'    => false,
                'is_filterable_in_grid' => false,
                'used_for_promo_rules'  => true,
                'required'              => false
            ],
            'aps_sub_interval_count' => [
                'type'                  => 'varchar',
                'label'                 => 'Repeat Every',
                'input'                 => 'text',
                'sort_order'            => 120,
                'global'                => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group'                 => $groupName,
                'is_used_in_grid'       => false,
                'is_visible_in_grid'    => false,
                'is_filterable_in_grid' => false,
                'used_for_promo_rules'  => true,
                'required'              => false
            ]
        ];

        $categorySetup = $this->_categorySetupFactory->create(['setup' => $setup]);

        foreach ($attributes as $code => $params) {
            $categorySetup->addAttribute(\Magento\Catalog\Model\Product::ENTITY, $code, $params);
        }

        $this->sortGroup($groupName, 11);
    }

    /**
     * @inheritdoc
     */
    private function sortGroup($attributeGroupName, $order)
    {
        $entityType = $this->_eavTypeFactory->create()->loadByCode('catalog_product');
        $setCollection = $this->_attributeSetFactory->create()->getCollection();
        $setCollection->addFieldToFilter('entity_type_id', $entityType->getId());

        foreach ($setCollection as $attributeSet) {
            $this->updateAttributes($attributeSet, $attributeGroupName, $order);
        }
    }

    /**
     * @inheritdoc
     */
    private function updateAttributes($attributeSet, $attributeGroupName, $order)
    {
        $group = $this->_groupCollectionFactory->create()
                ->addFieldToFilter('attribute_set_id', $attributeSet->getId())
                ->addFieldToFilter('attribute_group_name', $attributeGroupName)
                ->getFirstItem()
                ->setSortOrder($order)
                ->save();
    }
}
