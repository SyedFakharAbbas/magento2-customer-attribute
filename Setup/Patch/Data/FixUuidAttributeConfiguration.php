<?php
/**
 * Copyright © EliteRemoteFirm. All rights reserved.
 */
declare(strict_types=1);

namespace EliteRemoteFirm\CustomerAttribute\Setup\Patch\Data;

use EliteRemoteFirm\CustomerAttribute\Model\UuidGenerator;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Ensures the UUID attribute belongs to the customer attribute set and backfills missing values.
 */
class FixUuidAttributeConfiguration implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly CustomerSetupFactory $customerSetupFactory,
        private readonly EavSetupFactory $eavSetupFactory,
        private readonly CollectionFactory $customerCollectionFactory,
        private readonly UuidGenerator $uuidGenerator,
        private readonly IndexerRegistry $indexerRegistry,
        private readonly Config $eavConfig
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $entityTypeId = (int) $customerSetup->getEntityTypeId(Customer::ENTITY);
        $attributeId = (int) $eavSetup->getAttributeId(Customer::ENTITY, AddUuidCustomerAttribute::ATTRIBUTE_CODE);

        $customerSetup->updateAttribute(
            Customer::ENTITY,
            AddUuidCustomerAttribute::ATTRIBUTE_CODE,
            [
                'is_visible' => true,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true,
            ]
        );

        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, AddUuidCustomerAttribute::ATTRIBUTE_CODE);
        $attribute->setData('used_in_forms', []);
        $attribute->save();

        $select = $this->moduleDataSetup->getConnection()->select()
            ->from($this->moduleDataSetup->getTable('eav_attribute_set'), 'attribute_set_id')
            ->where('entity_type_id = ?', $entityTypeId);

        foreach ($this->moduleDataSetup->getConnection()->fetchCol($select) as $attributeSetId) {
            $eavSetup->addAttributeToSet(
                $entityTypeId,
                (int) $attributeSetId,
                'General',
                $attributeId,
                200
            );
        }

        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToSelect(AddUuidCustomerAttribute::ATTRIBUTE_CODE);
        $collection->addAttributeToFilter(
            AddUuidCustomerAttribute::ATTRIBUTE_CODE,
            [['null' => true], ['eq' => '']],
            'left'
        );

        foreach ($collection as $customer) {
            /** @var Customer $customer */
            $customer->setData(AddUuidCustomerAttribute::ATTRIBUTE_CODE, $this->uuidGenerator->generateUnique());
            $customer->getResource()->saveAttribute($customer, AddUuidCustomerAttribute::ATTRIBUTE_CODE);
        }

        $this->eavConfig->clear();
        $this->indexerRegistry->get(Customer::CUSTOMER_GRID_INDEXER_ID)->invalidate();

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [
            AddUuidCustomerAttribute::class,
            PopulateExistingCustomerUuids::class,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
