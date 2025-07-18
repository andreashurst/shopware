<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Core\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;

#[Package('fundamentals@after-sales')]
class Migration1593494859AddEntityUuidConnectionKeyToMapping extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1593494859;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE swag_migration_mapping ADD INDEX `idx.swag_migration_mapping.entity_uuid_connection_id` (`entity_uuid`, `connection_id`);');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
