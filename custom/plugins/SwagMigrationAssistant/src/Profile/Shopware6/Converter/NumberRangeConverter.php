<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\NumberRange\Aggregate\NumberRangeState\NumberRangeStateCollection;
use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Logging\LoggingServiceInterface;
use SwagMigrationAssistant\Migration\Mapping\Lookup\NumberRangeLookup;
use SwagMigrationAssistant\Migration\Mapping\Lookup\NumberRangeTypeLookup;
use SwagMigrationAssistant\Migration\Mapping\MappingServiceInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\Logging\Log\UnsupportedNumberRangeTypeLog;
use SwagMigrationAssistant\Profile\Shopware6\DataSelection\DataSet\NumberRangeDataSet;
use SwagMigrationAssistant\Profile\Shopware6\Shopware6MajorProfile;

#[Package('fundamentals@after-sales')]
class NumberRangeConverter extends ShopwareConverter
{
    /**
     * @param EntityRepository<NumberRangeStateCollection> $numberRangeStateRepository
     */
    public function __construct(
        MappingServiceInterface $mappingService,
        LoggingServiceInterface $loggingService,
        protected EntityRepository $numberRangeStateRepository,
        protected readonly NumberRangeLookup $numberRangeLookup,
        protected readonly NumberRangeTypeLookup $numberRangeTypeLookup,
    ) {
        parent::__construct($mappingService, $loggingService);
    }

    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile()->getName() === Shopware6MajorProfile::PROFILE_NAME
            && $this->getDataSetEntity($migrationContext) === NumberRangeDataSet::getEntity();
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;

        if (isset($converted['type']['technicalName'])) {
            $numberRangeTypeMapping = $this->mappingService->getMapping($this->connectionId, DefaultEntities::NUMBER_RANGE_TYPE, $converted['type']['technicalName'], $this->context);
            if ($numberRangeTypeMapping !== null) {
                $typeUuid = $numberRangeTypeMapping['entityUuid'];
            } else {
                $typeUuid = $this->numberRangeTypeLookup->get($converted['type']['technicalName'], $this->context);
                if ($typeUuid === null) {
                    $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
                        DefaultEntities::NUMBER_RANGE,
                        $data['id'],
                        $data['id']
                    );

                    $this->loggingService->addLogEntry(
                        new UnsupportedNumberRangeTypeLog(
                            $this->runId,
                            DefaultEntities::NUMBER_RANGE,
                            $data['id'],
                            $converted['type']['technicalName']
                        )
                    );

                    return new ConvertStruct(null, $data, $this->mainMapping['id'] ?? null);
                }
            }

            if ($converted['global']) {
                $this->checkForExistingNumberRange($converted);
            }

            if (isset($converted['numberRangeSalesChannels'])) {
                foreach ($converted['numberRangeSalesChannels'] as &$numberRangeSalesChannel) {
                    $numberRangeSalesChannel['numberRangeTypeId'] = $typeUuid;
                }
            }

            unset($numberRangeSalesChannel, $converted['type']);
            $converted['typeId'] = $typeUuid;
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::NUMBER_RANGE,
            $data['id'],
            $converted['id']
        );

        $this->updateAssociationIds(
            $converted['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::NUMBER_RANGE
        );

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }

    private function checkForExistingNumberRange(array &$converted): void
    {
        $existingId = $this->numberRangeLookup->get($converted['type']['technicalName'], $this->context);
        if ($existingId === null) {
            return;
        }

        $converted['id'] = $existingId;

        if (!isset($converted['state'])) {
            return;
        }

        $stateId = $this->getNumberRangeStateId($existingId);

        if ($stateId === null) {
            return;
        }

        $converted['state']['id'] = $stateId;
    }

    private function getNumberRangeStateId(string $numberRangeId): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('numberRangeId', $numberRangeId));
        $criteria->setLimit(1);

        return $this->numberRangeStateRepository->searchIds($criteria, $this->context)->firstId();
    }
}
