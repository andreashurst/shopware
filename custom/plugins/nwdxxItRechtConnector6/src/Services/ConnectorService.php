<?php declare(strict_types=1);

namespace Nwdxx\ItRechtConnector6\Services;

use ITRechtKanzlei\LTIError;
use ITRechtKanzlei\LTIPushData;
use ITRechtKanzlei\LTIPushResult;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Cache\EntityCacheKeyGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter;
use Shopware\Core\Framework\Uuid\Exception\InvalidUuidException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;


class ConnectorService extends \ITRechtKanzlei\LTIHandler
{
    use ConnectorServiceDependenciesTrait;

    private const SLOT_NAME_IDENTIFIER = 'it-recht-block';
    private const MEDIA_FOLDER_NAME = 'IT-Recht Kanzlei';

    private function getChannelByAccountId(string $id): SalesChannelEntity
    {
        $channel = null;
        $eMsg = '';
        $exception = null;
        try {
            $channel = $this->salesChannelRepository->search(
                (new Criteria([$id]))
                    ->addAssociation('countries')
                    ->addAssociation('language')
                    ->addAssociation('languages.locale')
                    ->addAssociation('domains.language'),
                $this->getSystemContext()
            )->first();
        } catch (InvalidUuidException $exception) {
            $eMsg = $exception->getMessage();
            if (empty($eMsg)) {
                $eMsg = 'InvalidUuidException.';
            }
        } catch (\Throwable $exception) {
            $eMsg = rtrim($exception->getMessage(), '.').'.';
        }
        if (!$channel instanceof SalesChannelEntity) {
            if (($channel !== null) && function_exists('get_debug_type')) {
                $eMsg = 'Unerwartetes Ergebnis: '.get_debug_type($channel).'. '.$eMsg;
            }
            throw new LTIError(
                trim('Der Verkaufskanal konnte nicht geladen werden. '.$eMsg),
                LTIError::INVALID_USER_ACCOUNT_ID,
                $exception
            );
        }
        return $channel;
    }

    private function getChannelContext(SalesChannelEntity $channel): Context
    {
        $source = new SalesChannelApiSource($channel->getId());
        return new Context($source);
    }

    private function isImportEnabledForChannelAndType(
        SalesChannelEntity $channel,
        LTIPushData $legalText
    ): bool {
        $tosEnalbed = (bool)$this->systemConfigService->get(
            'nwdxxItRechtConnector6.config.tosDownload',
            $channel->getId()
        );

        $revocationEnabled = (bool)$this->systemConfigService->get(
            'nwdxxItRechtConnector6.config.revocationDownload',
            $channel->getId()
        );

        return ($legalText->getType() === LTIPushData::DOCTYPE_TERMS_AND_CONDITIONS && $tosEnalbed === true)
            || ($legalText->getType() === LTIPushData::DOCTYPE_CAMCELLATION_POLICY  && $revocationEnabled === true);
    }

    private function resolveLanguage(
        string $langIso,
        string $countryIso,
        SalesChannelEntity $channel
    ): LanguageEntity {
        $countryFound = null;
        $countryFoundAT = null;
        $availableCountries = [];
        foreach ($channel->getCountries() as $id => $country) {
            $availableCountries[] = $country->getIso();
            if ($country->getIso() === $countryIso) {
                $countryFound = $country;
                break;
            } elseif (($countryIso === 'DE') && ($country->getIso() === 'AT')) {
                // DE legal texts apply usually to AT as well.
                $countryFoundAT = $country;
            }
        }
        if (($countryFound === null) && ($countryFoundAT === null)) {
            throw new LTIError(
                sprintf(
                    'Das Land, für den der Rechtstext verfasst ist (%s), ist in dem '
                        .'gewählten Verkaufskanal nicht aktiviert. '
                        .'Dem Verkaufskanal sind folgende Länder zugeordnet: %s.',
                    $countryIso,
                    implode(', ', $availableCountries)
                ),
                LTIError::INVALID_DOCUMENT_COUNTRY
            );
        }
        $languageFound = null;
        $availableLanguages = [];
        foreach ($channel->getLanguages() as $id => $language) {
            $availableLanguages[] = strtr($language->getLocale()->getCode(), '-', '_');
            $langCode = preg_replace('/^([a-z]{2,3})(-.*)/', '$1', $language->getLocale()->getCode());
            if ($langCode === $langIso) {
                $languageFound = $language;
                break;
            } elseif (($langCode === 'gsw') && ($langIso === 'de')) {
                // Maybe swiss german.
                $languageFound = $language;
                // Do not break; and check the other languages for a better match.
            }
        }

        if ($languageFound === null) {
            throw new LTIError(
                sprintf(
                    'Die Sprache, in der der Rechtstext verfasst ist (%s), ist in dem '
                        .'gewählten Verkaufskanal nicht aktiviert. '
                        .'Dem Verkaufskanal sind folgende Sprachen zugeordnet: %s.',
                    $langIso,
                    implode(', ', $availableLanguages)
                ),
                LTIError::INVALID_DOCUMENT_LANGUAGE
            );
        }
        return $languageFound;
    }

    private function findPageById(string $pageId, Context $context): CmsPageEntity
    {
        $criteria = (new Criteria([$pageId]))
            ->addAssociation('sections.blocks.slots')
            ->addAssociation('sections.blocks.slots.translations');

        $searchResult = $this->cmsPageRepository->search(
            $criteria,
            $context
        );

        $page = $searchResult->first();
        if ($page === null) {
            throw new LTIError(
                sprintf('Die Seite mit der ID %s konnte nicht geladen werden.', $pageId),
                LTIError::CONFIGURATION_DOCUMENT_NOT_FOUND
            );
        }

        return $searchResult->first();
    }

    private function getCmsPage(string $type, Context $context): CmsPageEntity
    {
        switch ($type) {
            case 'agb':
                $configKey = 'core.basicInformation.tosPage';
                break;
            case 'datenschutz':
                $configKey = 'core.basicInformation.privacyPage';
                break;
            case 'widerruf':
                $configKey = 'core.basicInformation.revocationPage';
                break;
            case 'impressum':
                $configKey = 'core.basicInformation.imprintPage';
                break;
            default:
                throw new LTIError(
                    sprintf('Der Rechtstexte-Typ %s wird nicht unterstützt.', $type),
                    LTIError::INVALID_DOCUMENT_TYPE
                );
        }

        $pageId = $this->systemConfigService->get(
            $configKey,
            $context->getSource()->getSalesChannelId()
        );

        if (null === $pageId) {
            throw new LTIError(
                sprintf('Für den Rechtstexte-Typ %s wurde noch keine Seite zugeordnet.', $type),
                LTIError::CONFIGURATION_DOCUMENT_NOT_FOUND
            );
        }
        return $this->findPageById($pageId, $context);
    }

    private function matchSlot(
        Context $context,
        CmsPageEntity $cmsPage,
        bool $retry = false
    ): ?CmsSlotEntity {
        // Search block
        $foundBlock = null;

        foreach ($cmsPage->getSections()->getBlocks() as $blockEntity) {
            if ($blockEntity->getName() === self::SLOT_NAME_IDENTIFIER) {
                $foundBlock = $blockEntity;
            }
        }

        // Try to create a new block with slot.
        if ($foundBlock === null && $retry === true) {
            $this->cmsPageRepository->update(
                [[
                    'id' => $cmsPage->getId(),
                    'sections' => [
                        [
                            'position' => 0,
                            'type' => 'default',
                            'blocks' => [
                                [
                                    'name' => self::SLOT_NAME_IDENTIFIER,
                                    'type' => 'text',
                                    'position' => 0,
                                    'slots' => [
                                        [
                                            'type' => 'text',
                                            'slot' => 'content',
                                            'config' => [
                                                'content' => [
                                                    'value' => 'it recht legal text',
                                                    'source' => 'static'
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]],
                $context
            );

            // We need to reload the page because the old will not hold the created block.
            $cmsPage = $this->findPageById($cmsPage->getId(), $context);
            return $this->matchSlot($context, $cmsPage, false);
        }

        if ($foundBlock === null && $retry === false) {
            throw new LTIError(
                sprintf(
                    'Der Block %1$s für die Seite %2$s konnte nicht %3$s werden.',
                    self::SLOT_NAME_IDENTIFIER,
                    $cmsPage->getName(),
                    $retry ? 'erstellt' : 'gefunden'
                ),
                LTIError::CONFIGURATION_DOCUMENT_NOT_FOUND
            );
        }

        return $foundBlock->getSlots()->first();
    }

    private function createSlotUpdateData(
        CmsSlotEntity $slot,
        LanguageEntity $language,
        string $legalTextHtml
    ): array {
        // Merge the old configuration with the new incoming.
        $config = array_merge(
            is_array($slot->getConfig()) ? $slot->getConfig() : [],
            ['content' => [
                'value' => $legalTextHtml,
                'source' => 'static'
            ]]
        );

        return [
            'id' => $slot->getId(),
            'translations' => [
                $language->getId() => [
                    'config' => $config
                ]
            ]
        ];
    }

    private function updateCategorySlotConfigs(
        CmsPageEntity $cmsPage,
        CmsSlotEntity $slot,
        LanguageEntity $language,
        string $legalTextHtml,
        Context $context
    ): void {
        $categories = $this->categoryRepository->search(
            (new Criteria())->addFilter(new Filter\EqualsFilter('cmsPageId', $cmsPage->getId())),
            $context
        );

        /** @var CategoryEntity $category */
        foreach ($categories as $category) {
            if (!isset($category->getSlotConfig()[$slot->getId()])) {
                continue;
            }
            $slotConfig = $category->getSlotConfig();
            $slotConfig[$slot->getId()]['content']['value'] = $legalTextHtml;
            $update = [
                'id' => $category->getId(),
                'translations' => [
                    $language->getId() => [
                        'slotConfig' => $slotConfig
                    ]
                ]
            ];
            $this->categoryRepository->upsert([$update], $context);
        }
    }

    private function invalidateCacheForContentPage(?CmsPageEntity $cmsPage): void
    {
        $tag = EntityCacheKeyGenerator::buildCmsTag($cmsPage->getId());
        $this->cacheClearer->deleteItems([$tag]);
    }

    private function makeMediaFolder(Context $context, string $ident, string $folderName, string $parentId)
    {
        $folder = $this->mediaFolderRepository->search(
            (new Criteria())->addFilter(new Filter\EqualsFilter('customFields.nwdxx_ident', $ident)),
            $context
        )->first();

        if ($folder === null) {
            $folderId = Uuid::randomHex();
            $folderData = [
                'id' => $folderId,
                'name' => $folderName,
                'configuration' => [
                    'id' => Uuid::randomHex()
                ],
                'customFields' => ['nwdxx_ident' => $ident]
            ];
            if (!empty($parentId)) {
                $folderData['parentId'] = $parentId;
            }
            $this->mediaFolderRepository->create([$folderData], $context);
            return $folderId;
        } else {
            return $folder->getId();
        }
    }

    private function isFilenameUnique(
        string $fileName,
        string $fileExtension,
        ?string $mediaId,
        Context $context
    ): bool {
        $andFilter = [
            new Filter\EqualsFilter('fileName', $fileName),
            new Filter\EqualsFilter('fileExtension', $fileExtension)
        ];
        if (!empty($mediaId)) {
            $andFilter[] = new Filter\NotFilter(
                Filter\NotFilter::CONNECTION_AND,
                [new Filter\EqualsFilter('id', $mediaId)]
            );
        }

        $criteria = new Criteria();
        $criteria->addFilter(new Filter\MultiFilter(Filter\MultiFilter::CONNECTION_AND, $andFilter));
        $result = $this->mediaRepository->search($criteria, $context);
        return $result->count() > 0;
    }

    private function importPdf(LTIPushData $legalText): void
    {
        if (!$legalText->hasPdf()) {
            return;
        }
        $channel = $this->getChannelByAccountId($legalText->getMultiShopId());
        if ($this->isImportEnabledForChannelAndType($channel, $legalText) === false) {
            return;
        }

        $context = $this->getChannelContext($channel);

        $channelIdShort = hash('crc32', $channel->getId(), false);
        $typeInLocaleForChannel = $channelIdShort . '-' . $legalText->getType() . '-' . $legalText->getLocale();

        // Default filename.
        $mediaFileName = $legalText->getLocalizedFileName();
        $mediaFileExtension = 'pdf';

        $criteria = new Criteria();
        $criteria->addFilter(
            new Filter\MultiFilter(Filter\MultiFilter::CONNECTION_OR, [
                new Filter\EqualsFilter('customFields.nwdxx_legal_type', $typeInLocaleForChannel),
                // Also search for the deprecated way of generating the value of this custom field.
                new Filter\EqualsFilter(
                    'customFields.nwdxx_legal_type',
                    substr($channel->getId(), 0, 5) . '-' . $legalText->getType() . '-' . $legalText->getLocale()
                )
            ])
        );

        $result = $this->mediaRepository->search($criteria, $context);
        if ($result->count() > 0) {
            /** @var MediaEntity $mediaEntity */
            $mediaEntity = $result->first();
            $mediaId = $mediaEntity->getId();
            if ($mediaEntity->getFileName()) {
                $mediaFileName = $mediaEntity->getFileName();
            }
        } else {
            try {
                $mediaFolderId = $this->makeMediaFolder(
                    $context,
                    $channel->getId(),
                    $channel->getName(),
                    $this->makeMediaFolder($context, 'itrk-root', self::MEDIA_FOLDER_NAME, '')
                );
            } catch (\Exception $e) {
                throw new LTIError(
                    'Das Verzeichnis zum Speichern des PDF Dokumentes konnte nicht angelegt werden.',
                    LTIError::SAVE_PDF_ERROR,
                    $e
                );
            }

            $mediaId = Uuid::randomHex();
            $this->mediaRepository->upsert([
                [
                    'id' => $mediaId,
                    'mediaFolderId' => $mediaFolderId,
                    'customFields' => [
                        'nwdxx_legal_type' => $typeInLocaleForChannel,
                    ]
                ]
            ], $context);
        }

        // Make sure, the file name is unique.
        if ($this->isFilenameUnique(
            $mediaFileName,
            $mediaFileExtension,
            $mediaId,
            $context
        )) {
            $mediaFileName .= '-'.$channelIdShort;
        }

        // Create the media file.
        $mediaFile = $this->fileFetcher->fetchBlob(
            $legalText->getPdf(),
            $mediaFileExtension,
            'application/pdf'
        );
        try {
            $this->fileSaver->persistFileToMedia(
                $mediaFile,
                $mediaFileName,
                $mediaId,
                $context
            );
        } catch (\Exception $e) {
            throw new LTIError(
                'Das PDF Dokument konnte nicht gespeichert werden.',
                LTIError::SAVE_PDF_ERROR,
                $e
            );
        }
    }

    /**
     * SDK METHOD
     *
     * Check whether the sent token is valid or not.
     */
    public function isTokenValid(string $token): bool
    {
        $tokenSetting = (string)$this->systemConfigService->get(
            'nwdxxItRechtConnector6.config.authToken'
        );
        return $tokenSetting === $token;
    }

    /**
     * SDK METHOD
     *
     * List all sales channels and their enabled languages.
     */
    public function handleActionGetAccountList(): \ITRechtKanzlei\LTIAccountListResult
    {
        $salesChannels = $this->salesChannelRepository->search(
            (new Criteria())
                ->addAssociation('languages.locale')
                ->addAssociation('countries')
                ->addAssociation('type')
                ->addFilter(new Filter\EqualsFilter('active', true))
                ->addFilter(new Filter\NotFilter(
                    Filter\NotFilter::CONNECTION_AND,
                    [new Filter\EqualsFilter('typeId', Defaults::SALES_CHANNEL_TYPE_PRODUCT_COMPARISON)]
                )),
            $this->getSystemContext()
        );

        $result = new \ITRechtKanzlei\LTIAccountListResult();
        /** @var \Shopware\Core\System\SalesChannel\SalesChannelEntity $channel */
        foreach ($salesChannels as $channel) {
            $languages = [];
            $countries = [];
            $name = $channel->getName().' ('.$channel->getType()->getName().')';
            foreach ($channel->getLanguages() as $lang) {
                $languages[] = strtr($lang->getLocale()->getCode(), ['-' => '_']);
            }
            foreach ($channel->getCountries() as $id => $country) {
                $countries[] = $country->getIso();
            }
            $result->addAccount((string)$channel->getId(), $name, $languages, $countries, [
                'salesCannelTypeId' => $channel->getType()->getId(),
            ]);
        }
        return $result;
    }

    /**
     * SDK METHOD
     *
     * Handle legal text updates.
     */
    public function handleActionPush(LTIPushData $legalText): LTIPushResult
    {
        $channel  = $this->getChannelByAccountId($legalText->getMultiShopId());
        $context  = $this->getChannelContext($channel);
        $language = $this->resolveLanguage($legalText->getLanguageIso639_1(), $legalText->getCountry(), $channel);
        $cmsPage  = $this->getCmsPage($legalText->getType(), $context);
        $slot     = $this->matchSlot($context, $cmsPage, true);

        $this->cmsSlotRepository->upsert(
            [$this->createSlotUpdateData($slot, $language, $legalText->getTextHtml())],
            $context
        );
        $this->updateCategorySlotConfigs($cmsPage, $slot, $language, $legalText->getTextHtml(), $context);
        $this->invalidateCacheForContentPage($cmsPage);

        $this->importPdf($legalText);

        $targetUrl = null;
        if (is_iterable($channel->getDomains())) {
            foreach ($channel->getDomains() as $domain) {
                if (($domain->getLanguage() !== null)
                    && ($domain->getLanguage()->getId() === $language->getId())
                    && !empty($domain->getUrl())
                ) {
                    $targetUrl = sprintf('%s/maintenance/singlepage/%s', $domain->getUrl(), $cmsPage->getId());
                    break;
                }
            }
        }

        return new LTIPushResult($targetUrl);
    }
}
