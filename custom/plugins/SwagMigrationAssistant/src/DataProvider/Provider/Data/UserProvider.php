<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\DataProvider\Provider\Data;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\User\UserCollection;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

#[Package('fundamentals@after-sales')]
class UserProvider extends AbstractProvider
{
    /**
     * @param EntityRepository<UserCollection> $adminUserRepo
     */
    public function __construct(private readonly EntityRepository $adminUserRepo)
    {
    }

    public function getIdentifier(): string
    {
        return DefaultEntities::USER;
    }

    public function getProvidedData(int $limit, int $offset, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);
        $criteria->addSorting(new FieldSorting('id'));
        $result = $this->adminUserRepo->search($criteria, $context);

        return $this->cleanupSearchResult($result, [
            'password',
        ]);
    }

    public function getProvidedTotal(Context $context): int
    {
        return $this->readTotalFromRepo($this->adminUserRepo, $context);
    }

    public function getProvidedTable(Context $context): array
    {
        return $this->readTableFromRepo($this->adminUserRepo, $context);
    }
}
