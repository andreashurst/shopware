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
class Migration1564041870AddConnectionKeyToMapping extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1564041870;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE swag_migration_mapping ADD INDEX `idx.swag_migration_mapping.connection_id_entity_old_identifier` (`connection_id`, `entity`, `old_identifier`);');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
