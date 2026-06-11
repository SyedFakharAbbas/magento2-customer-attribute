<?php
/**
 * Copyright © EliteRemoteFirm. All rights reserved.
 */
declare(strict_types=1);

namespace EliteRemoteFirm\CustomerAttribute\Plugin\CustomerRepository;

use EliteRemoteFirm\CustomerAttribute\Model\UuidGenerator;
use EliteRemoteFirm\CustomerAttribute\Setup\Patch\Data\AddUuidCustomerAttribute;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Assigns UUIDs to new customers and prevents modification of existing UUID values.
 */
class UuidPlugin
{
    public function __construct(
        private readonly UuidGenerator $uuidGenerator
    ) {
    }

    /**
     * Ensure every customer has a UUID and that existing UUIDs cannot be changed.
     *
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface $customer
     * @param mixed $passwordHash
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        CustomerRepositoryInterface $subject,
        CustomerInterface $customer,
        $passwordHash = null
    ): array {
        if (!$customer->getId()) {
            $this->assignUuidIfMissing($customer);
            return [$customer, $passwordHash];
        }

        $existingCustomer = $subject->getById((int) $customer->getId());
        $existingUuid = $this->getUuidValue($existingCustomer);

        if ($existingUuid !== null) {
            $customer->setCustomAttribute(AddUuidCustomerAttribute::ATTRIBUTE_CODE, $existingUuid);
            return [$customer, $passwordHash];
        }

        $this->assignUuidIfMissing($customer);

        return [$customer, $passwordHash];
    }

    /**
     * @throws LocalizedException
     */
    private function assignUuidIfMissing(CustomerInterface $customer): void
    {
        $currentUuid = $this->getUuidValue($customer);

        if ($currentUuid !== null && $currentUuid !== '') {
            return;
        }

        $customer->setCustomAttribute(
            AddUuidCustomerAttribute::ATTRIBUTE_CODE,
            $this->uuidGenerator->generateUnique()
        );
    }

    private function getUuidValue(CustomerInterface $customer): ?string
    {
        $attribute = $customer->getCustomAttribute(AddUuidCustomerAttribute::ATTRIBUTE_CODE);

        if (!$attribute instanceof AttributeInterface) {
            return null;
        }

        $value = $attribute->getValue();

        return $value === null || $value === '' ? null : (string) $value;
    }
}
