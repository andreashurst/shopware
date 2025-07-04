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
class SalesChannelReader extends AbstractReader implements ReaderInterface
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === DefaultEntities::SALES_CHANNEL;
    }

    public function supportsTotal(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareLocalGateway::GATEWAY_NAME;
    }

    public function read(MigrationContextInterface $migrationContext): array
    {
        $fetchedSalesChannels = $this->fetchData($migrationContext->getOffset(), $migrationContext->getLimit(), $migrationContext);
        $salesChannels = $this->mapData($fetchedSalesChannels, [], ['shop', 'locale', 'currency']);

        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale($migrationContext);

        foreach ($salesChannels as $key => &$salesChannel) {
            $salesChannel['locale'] = \str_replace('_', '-', $salesChannel['locale']);
            $salesChannel['_locale'] = \str_replace('_', '-', $locale);
            if (!empty($salesChannel['main_id'])) {
                $salesChannels[$salesChannel['main_id']]['children'][] = $salesChannel;
                unset($salesChannels[$key]);
            }
        }
        $salesChannels = \array_values($salesChannels);

        return $this->cleanupResultSet($salesChannels);
    }

    public function readTotal(MigrationContextInterface $migrationContext): ?TotalStruct
    {
        $connection = $this->getConnection($migrationContext);

        $total = (int) $connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('s_core_shops')
            ->executeQuery()
            ->fetchOne();

        return new TotalStruct(DefaultEntities::SALES_CHANNEL, $total);
    }

    /**
     * @return array<mixed>
     */
    private function fetchData(int $offset, int $limit, MigrationContextInterface $migrationContext): array
    {
        $ids = $this->fetchIdentifiers($migrationContext, 's_core_shops', $offset, $limit);
        $connection = $this->getConnection($migrationContext);
        $query = $connection->createQueryBuilder();

        $query->from('s_core_shops', 'shop');
        $query->addSelect('shop.id as identifier');
        $this->addTableSelection($query, 's_core_shops', 'shop', $migrationContext);

        $query->leftJoin('shop', 's_core_locales', 'locale', 'shop.locale_id = locale.id');
        $query->addSelect('locale.locale');

        $query->leftJoin('shop', 's_core_currencies', 'currency', 'shop.currency_id = currency.id');
        $query->addSelect('currency.currency');

        $query->where('shop.id IN (:ids)');
        $query->setParameter('ids', $ids, ArrayParameterType::STRING);
        $query->orderBy('shop.main_id');

        return FetchModeHelper::groupUnique($query->executeQuery()->fetchAllAssociative());
    }
}
