<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware\Media;

use GuzzleHttp\Promise\PromiseInterface;
use Shopware\Core\Framework\Log\Package;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;
use SwagMigrationAssistant\Migration\Gateway\HttpClientInterface;
use SwagMigrationAssistant\Migration\Gateway\HttpSimpleClient;
use SwagMigrationAssistant\Migration\Media\Processor\HttpDownloadServiceBase;
use SwagMigrationAssistant\Migration\MigrationContextInterface;
use SwagMigrationAssistant\Profile\Shopware\DataSelection\DataSet\MediaDataSet;
use SwagMigrationAssistant\Profile\Shopware\Gateway\Api\ShopwareApiGateway;
use SwagMigrationAssistant\Profile\Shopware\ShopwareProfileInterface;

#[Package('fundamentals@after-sales')]
class HttpMediaDownloadService extends HttpDownloadServiceBase
{
    public function supports(MigrationContextInterface $migrationContext): bool
    {
        return $migrationContext->getProfile() instanceof ShopwareProfileInterface
            && $migrationContext->getGateway()->getName() === ShopwareApiGateway::GATEWAY_NAME
            && $this->getDataSetEntity($migrationContext) === MediaDataSet::getEntity();
    }

    protected function getMediaEntity(): string
    {
        return DefaultEntities::MEDIA;
    }

    protected function getHttpClient(MigrationContextInterface $migrationContext): ?HttpClientInterface
    {
        return new HttpSimpleClient();
    }

    protected function httpRequest(HttpClientInterface $client, array $additionalData): PromiseInterface
    {
        return $client->getAsync(
            $additionalData['uri'],
            [
                'query' => ['alt' => 'media'],
            ]
        );
    }
}
