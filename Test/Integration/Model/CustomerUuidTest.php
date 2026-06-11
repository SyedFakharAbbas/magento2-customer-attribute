<?php
/**
 * Copyright © EliteRemoteFirm. All rights reserved.
 */
declare(strict_types=1);

namespace EliteRemoteFirm\CustomerAttribute\Test\Integration\Model;

use EliteRemoteFirm\CustomerAttribute\Setup\Patch\Data\AddUuidCustomerAttribute;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for customer UUID attribute behaviour.
 *
 * @magentoAppArea frontend
 * @magentoDbIsolation enabled
 */
class CustomerUuidTest extends TestCase
{
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var CustomerInterfaceFactory
     */
    private CustomerInterfaceFactory $customerFactory;

    /**
     * @var AttributeValueFactory
     */
    private AttributeValueFactory $attributeValueFactory;

    /**
     * @var Config
     */
    private Config $eavConfig;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->customerRepository = $objectManager->get(CustomerRepositoryInterface::class);
        $this->customerFactory = $objectManager->get(CustomerInterfaceFactory::class);
        $this->attributeValueFactory = $objectManager->get(AttributeValueFactory::class);
        $this->eavConfig = $objectManager->get(Config::class);
    }

    /**
     * @return void
     */
    public function testUuidAttributeExistsAndIsUnique(): void
    {
        $attribute = $this->eavConfig->getAttribute('customer', AddUuidCustomerAttribute::ATTRIBUTE_CODE);

        $this->assertTrue((bool) $attribute->getId());
        $this->assertTrue((bool) $attribute->getIsUnique());
        $this->assertTrue((bool) $attribute->getData('is_visible_in_grid'));
        $this->assertSame([], $attribute->getData('used_in_forms'));
    }

    /**
     * Verify new customers receive a UUID automatically on save.
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @return void
     */
    public function testNewCustomerReceivesUuidAutomatically(): void
    {
        $email = 'uuid-test-' . uniqid('', true) . '@example.com';
        $customer = $this->customerFactory->create();
        $customer->setEmail($email);
        $customer->setFirstname('Uuid');
        $customer->setLastname('Test');
        $customer->setWebsiteId(1);

        $savedCustomer = $this->customerRepository->save($customer);
        $savedCustomer = $this->customerRepository->getById((int) $savedCustomer->getId());

        $uuid = $savedCustomer->getCustomAttribute(AddUuidCustomerAttribute::ATTRIBUTE_CODE);

        $this->assertNotNull($uuid);
        $this->assertNotEmpty($uuid->getValue());
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            (string) $uuid->getValue()
        );

        $this->customerRepository->delete($savedCustomer);
    }

    /**
     * Verify an existing UUID cannot be modified via customer repository save.
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @return void
     */
    public function testExistingUuidCannotBeModified(): void
    {
        $email = 'uuid-immutable-' . uniqid('', true) . '@example.com';
        $customer = $this->customerFactory->create();
        $customer->setEmail($email);
        $customer->setFirstname('Immutable');
        $customer->setLastname('Uuid');
        $customer->setWebsiteId(1);

        $savedCustomer = $this->customerRepository->save($customer);
        $savedCustomer = $this->customerRepository->getById((int) $savedCustomer->getId());
        $originalUuid = (string) $savedCustomer->getCustomAttribute(AddUuidCustomerAttribute::ATTRIBUTE_CODE)
            ->getValue();

        $attribute = $this->attributeValueFactory->create();
        $attribute->setAttributeCode(AddUuidCustomerAttribute::ATTRIBUTE_CODE);
        $attribute->setValue('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $savedCustomer->setCustomAttribute(AddUuidCustomerAttribute::ATTRIBUTE_CODE, $attribute->getValue());

        $updatedCustomer = $this->customerRepository->save($savedCustomer);
        $updatedCustomer = $this->customerRepository->getById((int) $updatedCustomer->getId());

        $this->assertSame(
            $originalUuid,
            $updatedCustomer->getCustomAttribute(AddUuidCustomerAttribute::ATTRIBUTE_CODE)->getValue()
        );

        $this->customerRepository->delete($updatedCustomer);
    }
}
