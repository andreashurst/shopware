<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Migration\Data;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Log\Package;

/**
 * @extends EntityCollection<SwagMigrationDataEntity>
 */
#[Package('fundamentals@after-sales')]
class SwagMigrationDataCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return SwagMigrationDataEntity::class;
    }
}
