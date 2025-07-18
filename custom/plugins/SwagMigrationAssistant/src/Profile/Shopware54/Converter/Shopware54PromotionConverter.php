<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware54\Converter;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Converter\PromotionConverter;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\PromotionDataSet;
use SwagMigrationAssistant\Profile\Shopware54\Shopware54Profile;

#[Package('fundamentals@after-sales')]
class Shopware54PromotionConverter extends PromotionConverter
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware54Profile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === PromotionDataSet::getEntity();
    }
}
