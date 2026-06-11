<?php
/**
 * Copyright © EliteRemoteFirm. All rights reserved.
 */
declare(strict_types=1);

namespace EliteRemoteFirm\CustomerAttribute\Test\Unit\Model;

use EliteRemoteFirm\CustomerAttribute\Model\UuidGenerator;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UuidGeneratorTest extends TestCase
{
    /** @var ResourceConnection&MockObject */
    private ResourceConnection $resourceConnection;

    /** @var AdapterInterface&MockObject */
    private AdapterInterface $connection;

    private UuidGenerator $uuidGenerator;

    protected function setUp(): void
    {
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->resourceConnection->method('getConnection')->willReturn($this->connection);
        $this->resourceConnection->method('getTableName')->willReturnCallback(
            static fn (string $tableName): string => $tableName
        );

        $this->uuidGenerator = new UuidGenerator($this->resourceConnection);
    }

    public function testGenerateUniqueReturnsValidUuid(): void
    {
        $select = $this->createMock(Select::class);
        $this->connection->method('select')->willReturn($select);
        $select->method('from')->willReturnSelf();
        $select->method('join')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('limit')->willReturnSelf();
        $this->connection->method('fetchOne')->willReturn(false);

        $uuid = $this->uuidGenerator->generateUnique();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    public function testGenerateUniqueRetriesWhenCollisionOccurs(): void
    {
        $select = $this->createMock(Select::class);
        $this->connection->method('select')->willReturn($select);
        $select->method('from')->willReturnSelf();
        $select->method('join')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('limit')->willReturnSelf();
        $this->connection->method('fetchOne')->willReturnOnConsecutiveCalls(1, false);

        $uuid = $this->uuidGenerator->generateUnique();

        $this->assertNotEmpty($uuid);
    }
}
