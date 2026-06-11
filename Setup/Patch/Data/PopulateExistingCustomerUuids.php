<?php
/**
 * Copyright © EliteRemoteFirm. All rights reserved.
 */
declare(strict_types=1);

namespace EliteRemoteFirm\CustomerAttribute\Setup\Patch\Data;

use EliteRemoteFirm\CustomerAttribute\Model\UuidGenerator;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Assigns UUIDs to all existing customers that do not yet have one.
 */
class PopulateExistingCustomerUuids implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly CollectionFactory $customerCollectionFactory,
        private readonly UuidGenerator $uuidGenerator,
        private readonly IndexerRegistry $indexerRegistry
    ) {
    }

    /**
     * @inheritdoc
     */
    public function apply(): self
    {
        $this->moduleDataSetup->getConnection()->startSetup();

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
