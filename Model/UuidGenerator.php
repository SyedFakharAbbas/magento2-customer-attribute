<?php
/**
 * Copyright © EliteRemoteFirm. All rights reserved.
 */
declare(strict_types=1);

namespace EliteRemoteFirm\CustomerAttribute\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Ramsey\Uuid\Uuid;

/**
 * Generates RFC 4122 version 4 UUIDs and verifies uniqueness against stored customer values.
 */
class UuidGenerator
{
    private const ATTRIBUTE_CODE = 'uuid';
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Generate a UUID that does not already exist on a customer record.
     *
     * @throws LocalizedException
     */
    public function generateUnique(): string
    {
        $connection = $this->resourceConnection->getConnection();

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $uuid = Uuid::uuid4()->toString();

            if (!$this->uuidExists($connection, $uuid)) {
                return $uuid;
            }
        }

        throw new LocalizedException(__('Unable to generate a unique customer UUID.'));
    }

    /**
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     */
    private function uuidExists($connection, string $uuid): bool
    {
        $select = $connection->select()
            ->from(['cev' => $this->resourceConnection->getTableName('customer_entity_varchar')], 'value_id')
            ->join(
                ['ea' => $this->resourceConnection->getTableName('eav_attribute')],
                'cev.attribute_id = ea.attribute_id',
                []
            )
            ->where('ea.attribute_code = ?', self::ATTRIBUTE_CODE)
            ->where('cev.value = ?', $uuid)
            ->limit(1);

        return (bool) $connection->fetchOne($select);
    }
}
