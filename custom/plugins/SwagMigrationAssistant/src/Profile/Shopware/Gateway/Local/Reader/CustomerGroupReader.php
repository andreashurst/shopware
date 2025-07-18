<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Gateway\Local\Reader;

use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\Reader\ReaderInterface;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Migration\TotalStruct;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Local\ShopwareLocalGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('fundamentals@after-sales')]
class CustomerGroupReader extends AbstractReader implements ReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::CUSTOMER_GROUP;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $fetchedCustomerGroups = $this->fetchCustomerGroups($migrationContext);
        $groupIds = \array_column($fetchedCustomerGroups, 'customerGroup.id');
        $customerGroups = $this->mapData($fetchedCustomerGroups, [], ['customerGroup']);

        $fetchedDiscounts = $this->fetchCustomerGroupDiscounts($groupIds, $migrationContext);
        $discounts = $this->mapData($fetchedDiscounts, [], ['discount']);

        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale($migrationContext);

        foreach ($customerGroups as &$customerGroup) {
            $customerGroup['_locale'] = \str_replace('_', '-', $locale);
            if (isset($discounts[$customerGroup['id']])) {
                $customerGroup['discounts'] = $discounts[$customerGroup['id']];
            }
        }
        unset($customerGroup);

        return $this->cleanupResultSet($customerGroups);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $connection = $this->getConnection($migrationContext);

        $total = (int) $connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_core_customergroups')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::CUSTOMER_GROUP, $total);
    }

    private function fetchCustomerGroups(MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers($migrationContext, 's_core_customergroups', $migrationContext->getOffset(), $migrationContext->getLimit());
        $connection = $this->getConnection($migrationContext);

        $query = $connection->createQueryBuilder();

        $query->from('s_core_customergroups', 'customerGroup');
        $this->addTableSelection($query, 's_core_customergroups', 'customerGroup', $migrationContext);

        $query->leftJoin('customerGroup', 's_core_customergroups_attributes', 'customerGroup_attributes', 'customerGroup.id = customerGroup_attributes.customerGroupID');
        $this->addTableSelection($query, 's_core_customergroups_attributes', 'customerGroup_attributes', $migrationContext);

        $query->where('customerGroup.id IN (:ids)');
        $query->setParameter('ids', $ids, ArrayParameterType::STRING);

        $query->addOrderBy('customerGroup.id');

        $query->executeQuery();

        return $query->fetchAllAssociative();
    }

    private function fetchCustomerGroupDiscounts(array $groupIds, MigrationContextInterface $migrationContext): array
    {
        $connection = $this->getConnection($migrationContext);
        $query = $connection->createQueryBuilder();

        $query->from('s_core_customergroups_discounts', 'discount');
        $query->addSelect('groupID');
        $this->addTableSelection($query, 's_core_customergroups_discounts', 'discount', $migrationContext);

        $query->where('groupID IN (:ids)');
        $query->setParameter('ids', $groupIds, ArrayParameterType::STRING);

        return FetchModeHelper::group($query->executeQuery()->fetchAllAssociative());
    }
}
