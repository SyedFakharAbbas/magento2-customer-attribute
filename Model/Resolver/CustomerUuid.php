<?php
/**
 * Copyright © EliteRemoteFirm. All rights reserved.
 */
declare(strict_types=1);

namespace EliteRemoteFirm\CustomerAttribute\Model\Resolver;

use EliteRemoteFirm\CustomerAttribute\Setup\Patch\Data\AddUuidCustomerAttribute;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Resolves the customer UUID field for GraphQL queries.
 */
class CustomerUuid implements ResolverInterface
{
    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ): ?string {
        if (!is_array($value)) {
            return null;
        }

        $customer = $value['model'] ?? null;

        if ($customer instanceof CustomerInterface) {
            $attribute = $customer->getCustomAttribute(AddUuidCustomerAttribute::ATTRIBUTE_CODE);

            if ($attribute !== null && $attribute->getValue() !== null && $attribute->getValue() !== '') {
                return (string) $attribute->getValue();
            }
        }

        if (!empty($value['custom_attributes']) && is_array($value['custom_attributes'])) {
            foreach ($value['custom_attributes'] as $customAttribute) {
                if (($customAttribute['code'] ?? null) === AddUuidCustomerAttribute::ATTRIBUTE_CODE) {
                    return isset($customAttribute['value']) ? (string) $customAttribute['value'] : null;
                }
            }
        }

        return null;
    }
}
