<?php
/**
 * Copyright © EliteRemoteFirm. All rights reserved.
 */
declare(strict_types=1);

namespace EliteRemoteFirm\CustomerAttribute\Test\Unit\Plugin\CustomerRepository;

use EliteRemoteFirm\CustomerAttribute\Model\UuidGenerator;
use EliteRemoteFirm\CustomerAttribute\Plugin\CustomerRepository\UuidPlugin;
use EliteRemoteFirm\CustomerAttribute\Setup\Patch\Data\AddUuidCustomerAttribute;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\AttributeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UuidPluginTest extends TestCase
{
    /** @var UuidGenerator&MockObject */
    private UuidGenerator $uuidGenerator;

    /** @var CustomerRepositoryInterface&MockObject */
    private CustomerRepositoryInterface $customerRepository;

    private UuidPlugin $plugin;

    protected function setUp(): void
    {
        $this->uuidGenerator = $this->createMock(UuidGenerator::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->plugin = new UuidPlugin($this->uuidGenerator);
    }

    public function testBeforeSaveAssignsUuidForNewCustomer(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getId')->willReturn(null);
        $customer->method('getCustomAttribute')->with(AddUuidCustomerAttribute::ATTRIBUTE_CODE)->willReturn(null);

        $this->uuidGenerator->expects($this->once())
            ->method('generateUnique')
            ->willReturn('11111111-1111-4111-8111-111111111111');

        $customer->expects($this->once())
            ->method('setCustomAttribute')
            ->with(AddUuidCustomerAttribute::ATTRIBUTE_CODE, '11111111-1111-4111-8111-111111111111');

        $result = $this->plugin->beforeSave($this->customerRepository, $customer);

        $this->assertSame($customer, $result[0]);
    }

    public function testBeforeSavePreservesExistingUuid(): void
    {
        $customer = $this->createMock(CustomerInterface::class);
        $customer->method('getId')->willReturn(10);

        $existingCustomer = $this->createMock(CustomerInterface::class);
        $existingAttribute = $this->createMock(AttributeInterface::class);
        $existingAttribute->method('getValue')->willReturn('22222222-2222-4222-8222-222222222222');
        $existingCustomer->method('getCustomAttribute')
            ->with(AddUuidCustomerAttribute::ATTRIBUTE_CODE)
            ->willReturn($existingAttribute);

        $this->customerRepository->method('getById')->with(10)->willReturn($existingCustomer);

        $customer->expects($this->once())
            ->method('setCustomAttribute')
            ->with(AddUuidCustomerAttribute::ATTRIBUTE_CODE, '22222222-2222-4222-8222-222222222222');

        $this->uuidGenerator->expects($this->never())->method('generateUnique');

        $this->plugin->beforeSave($this->customerRepository, $customer);
    }
}
