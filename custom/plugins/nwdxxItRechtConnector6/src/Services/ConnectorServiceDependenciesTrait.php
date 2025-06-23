<?php

namespace Nwdxx\ItRechtConnector6\Services;

use Shopware\Core\Content\Media\File\FileFetcher;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Framework\Adapter\Cache\CacheClearer;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Routing\RouterInterface;

trait ConnectorServiceDependenciesTrait
{
    protected CacheClearer $cacheClearer;
    /** @var EntityRepository $salesChannelRepository  For older SW 6.4 this is an instance of SalesChannelRepositoryDecorator */
    protected $salesChannelRepository;
    protected EntityRepository $cmsPageRepository;
    protected EntityRepository $cmsSlotRepository;
    protected EntityRepository $cmsBlockRepository;
    protected EntityRepository $categoryRepository;
    protected EntityRepository $customFieldSetRepository;
    protected EntityRepository $languageRepository;
    protected EntityRepository $localeRepository;
    protected FileFetcher $fileFetcher;
    protected FileSaver $fileSaver;
    /** @var EntityRepository $mediaRepository  For older SW 6.4 this is an instance of MediaRepositoryDecorator */
    protected $mediaRepository;
    protected $mediaFolderRepository;
    protected RouterInterface $router;
    protected SystemConfigService $systemConfigService;

    public function setCacheClearer(CacheClearer $cacheClearer): void
    {
        $this->cacheClearer = $cacheClearer;
    }

    /**
     * @param EntityRepository|\Swag\Markets\DataAbstractionLayer\SalesChannelRepositoryDecorator $salesChannelRepository
     *     Sales Channel Repository. It is unclear at which SW 6.4 version it got switched to an EntityRepository.
     */
    public function setSalesChannelRepository($salesChannelRepository): void
    {
        if (!($salesChannelRepository instanceof EntityRepository
            || (
                class_exists(\Swag\Markets\DataAbstractionLayer\SalesChannelRepositoryDecorator::class)
                && $salesChannelRepository instanceof \Swag\Markets\DataAbstractionLayer\SalesChannelRepositoryDecorator
            )
        )) {
            throw new \InvalidArgumentException('Repository must be either of type EntityRepository or SalesChannelRepositoryDecorator');
        }
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function setCmsPageRepository(EntityRepository $cmsPageRepository): void
    {
        $this->cmsPageRepository = $cmsPageRepository;
    }

    public function setCmsSlotRepository(EntityRepository $cmsSlotRepository): void
    {
        $this->cmsSlotRepository = $cmsSlotRepository;
    }

    public function setCmsBlockRepository(EntityRepository $cmsBlockRepository): void
    {
        $this->cmsBlockRepository = $cmsBlockRepository;
    }

    public function setCategoryRepository(EntityRepository $categoryRepository): void
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function setCustomFieldSetRepository(EntityRepository $customFieldSetRepository): void
    {
        $this->customFieldSetRepository = $customFieldSetRepository;

        $this->createCustomFieldSetIfNotExits();
    }

    public function setLanguageRepository(EntityRepository $languageRepository): void
    {
        $this->languageRepository = $languageRepository;
    }

    public function setLocaleRepository(EntityRepository $localeRepository): void
    {
        $this->localeRepository = $localeRepository;
    }

    public function setFileFetcher(FileFetcher $fileFetcher): void
    {
        $this->fileFetcher = $fileFetcher;
    }

    public function setFileSaver(FileSaver $fileSaver): void
    {
        $this->fileSaver = $fileSaver;
    }

    public function setMediaRepository($mediaRepository): void
    {
        if (!($mediaRepository instanceof EntityRepository
            || (
                class_exists(\Shopware\Core\Content\Media\DataAbstractionLayer\MediaRepositoryDecorator::class)
                && $mediaRepository instanceof \Shopware\Core\Content\Media\DataAbstractionLayer\MediaRepositoryDecorator
            )
        )) {
            throw new \InvalidArgumentException('Repository must be either of type EntityRepository or MediaRepositoryDecorator');
        }
        $this->mediaRepository = $mediaRepository;
    }

    public function setMediaFolderRepository($mediaFolderRepository) {
        if (!($mediaFolderRepository instanceof EntityRepository
            || (
                class_exists(\Shopware\Core\Content\Media\DataAbstractionLayer\MediaFolderRepositoryDecorator::class)
                && $mediaFolderRepository instanceof \Shopware\Core\Content\Media\DataAbstractionLayer\MediaFolderRepositoryDecorator
            )
        )) {
            throw new \InvalidArgumentException('Repository must be either of type EntityRepository or MediaFolderRepositoryDecorator');
        }
        $this->mediaFolderRepository = $mediaFolderRepository;
    }

    public function setRouter(RouterInterface $router): void
    {
        $this->router = $router;
    }

    public function setSystemConfigService(SystemConfigService $systemConfigService): void
    {
        $this->systemConfigService = $systemConfigService;
    }

    protected function getSystemContext(): Context
    {
        return new Context(new SystemSource());
    }

    private function createCustomFieldSetIfNotExits()
    {
        $context = $this->getSystemContext();
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('name', 'nwdxx_legal_type_set')
        );

        $searchResult = $this->customFieldSetRepository->search($criteria, $context);
        if ($searchResult->count() === 0) {
            $this->customFieldSetRepository->upsert([
                [
                    'name' => 'nwdxx_legal_type_set',
                    'customFields' => [
                        [
                            'name' => 'nwdxx_legal_type',
                            'type' => CustomFieldTypes::TEXT
                        ],
                    ]
                ]
            ], $context);
        }
    }
}
