<?php
/**
 * Copyright © EliteRemoteFirm. All rights reserved.
 */
declare(strict_types=1);

namespace EliteRemoteFirm\CustomerAttribute\Plugin\Customer\DataProvider;

use EliteRemoteFirm\CustomerAttribute\Setup\Patch\Data\AddUuidCustomerAttribute;
use Magento\Customer\Model\Customer\DataProviderWithDefaultAddresses;

/**
 * Renders the UUID field as disabled/read-only on the admin customer form.
 */
class UuidReadOnlyPlugin
{
    /**
     * @param DataProviderWithDefaultAddresses $subject
     * @param array $meta
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetMeta(DataProviderWithDefaultAddresses $subject, array $meta): array
    {
        $attributeCode = AddUuidCustomerAttribute::ATTRIBUTE_CODE;

        if (!isset($meta['customer']['children'][$attributeCode])) {
            return $meta;
        }

        $meta['customer']['children'][$attributeCode]['arguments']['data']['config']['disabled'] = true;
        $meta['customer']['children'][$attributeCode]['arguments']['data']['config']['notice'] = __(
            'System-generated unique identifier. This value cannot be edited.'
        );
        unset($meta['customer']['children'][$attributeCode]['arguments']['data']['config']['service']);

        return $meta;
    }
}
