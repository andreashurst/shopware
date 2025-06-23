<?php declare(strict_types=1);

namespace Nwdxx\ItRechtConnector6;

use Nwdxx\ItRechtConnector6\Exceptions\InstallException;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Uuid\Uuid;

class nwdxxItRechtConnector6 extends Plugin
{
    public function install(InstallContext $context): void
    {
        $this->versionComparison($context->getCurrentShopwareVersion());
        parent::install($context);
        $this->createDefaultConfig(
            'nwdxxItRechtConnector6.config.authToken',
            Uuid::randomHex(),
            true
        );
    }

    public function update(UpdateContext $updateContext): void
    {
        $this->versionComparison($updateContext->getCurrentShopwareVersion());
        parent::update($updateContext);
    }

    public function activate(ActivateContext $activateContext): void
    {
        $this->versionComparison($activateContext->getCurrentShopwareVersion());
        parent::activate($activateContext);
    }

    private function versionComparison(string $shopVersion): void
    {
        if (version_compare($shopVersion, '6.4.10', '<')) {
            throw new InstallException(
                'Your shop version is not compatible with the current plugin version.'
            );
        }
    }

    private function createDefaultConfig($key, $value, $renew = false): void
    {
        $data = [
            'configurationKey' => $key,
            'configurationValue' => $value
        ];

        $defaultContext = new Context(new SystemSource());

        /** @var EntityRepository $repository */
        $repository = $this->container->get('system_config.repository');

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('configurationKey', $key)
        );

        $config = $repository->search($criteria, $defaultContext);
        if ($config->getTotal() === 0) {
            $repository->create([$data], $defaultContext);
            return;
        }

        if ($config->getTotal() > 0 && $renew === true) {
            $data['id'] = $config->first()->getId();
            $repository->update([$data], $defaultContext);
        }
    }
}
