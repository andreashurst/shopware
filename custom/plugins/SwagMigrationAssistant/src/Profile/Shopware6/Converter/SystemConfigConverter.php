<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\SystemConfigLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\SystemConfigDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class SystemConfigConverter extends ShopwareConverter
{
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        private readonly SystemConfigLookup $systemConfigLookup,
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === SystemConfigDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        $systemConfigUuid = $this->systemConfigLookup->get($data['configurationKey'], $data['salesChannelId'] ?? null, $this->context);
        if ($systemConfigUuid !== null) {
            $converted['id'] = $systemConfigUuid;
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::SYSTEM_CONFIG,
            $data['id'],
            $converted['id']
        );

        if (isset($converted['salesChannelId'])) {
            $converted['salesChannelId'] = $this->getMappingIdFacade(
                DefaultEntities::SALES_CHANNEL,
                $converted['salesChannelId']
            );
        }

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
