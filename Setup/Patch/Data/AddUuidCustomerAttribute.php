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
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Creates the customer UUID attribute and assigns UUIDs to all existing customers.
 */
class AddUuidCustomerAttribute implements DataPatchInterface
{
    public const ATTRIBUTE_CODE = 'uuid';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param CustomerSetupFactory $customerSetupFactory
     * @param CollectionFactory $customerCollectionFactory
     * @param UuidGenerator $uuidGenerator
     * @param IndexerRegistry $indexerRegistry
     */
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly CustomerSetupFactory $customerSetupFactory,
        private readonly CollectionFactory $customerCollectionFactory,
        private readonly UuidGenerator $uuidGenerator,
        private readonly IndexerRegistry $indexerRegistry
    ) {
    }

    /**
     * Create UUID attribute and backfill existing customers.
     *
     * Runs during module installation via setup:upgrade.
     *
     * @throws LocalizedException
     * @return $this
     */
    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        $this->createUuidAttribute();
        $this->assignUuidsToExistingCustomers();

        $this->indexerRegistry->get(Customer::CUSTOMER_GRID_INDEXER_ID)->invalidate();

        $this->moduleDataSetup->getConnection()->endSetup();

        return $this;
    }

    /**
     * Create and configure the customer UUID EAV attribute.
     *
     * @throws LocalizedException
     * @return void
     */
    private function createUuidAttribute(): void
    {
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $customerSetup->addAttribute(
            Customer::ENTITY,
            self::ATTRIBUTE_CODE,
            [
                'type' => 'varchar',
                'label' => 'UUID',
                'input' => 'text',
                'required' => false,
                'visible' => true,
                'user_defined' => true,
                'system' => false,
                'unique' => true,
                'global' => ScopedAttributeInterface::SCOPE_GLOBAL,
                'group' => 'General',
                'sort_order' => 200,
                'is_used_in_grid' => true,
                'is_visible_in_grid' => true,
                'is_filterable_in_grid' => true,
                'is_searchable_in_grid' => true,
                'position' => 200,
            ]
        );

        $attribute = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, self::ATTRIBUTE_CODE);
        $attribute->setData('used_in_forms', []);
        $attribute->save();
    }

    /**
     * Assign UUIDs to existing customers that do not have one.
     *
     * @throws LocalizedException
     * @return void
     */
    private function assignUuidsToExistingCustomers(): void
    {
        $collection = $this->customerCollectionFactory->create();
        $collection->addAttributeToSelect(self::ATTRIBUTE_CODE);
        $collection->addAttributeToFilter(
            self::ATTRIBUTE_CODE,
            [['null' => true], ['eq' => '']],
            'left'
        );

        foreach ($collection as $customer) {
            /** @var Customer $customer */
            $customer->setData(self::ATTRIBUTE_CODE, $this->uuidGenerator->generateUnique());
            $customer->getResource()->saveAttribute($customer, self::ATTRIBUTE_CODE);
        }
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
