<?php

namespace Frosh\TemplateMail\Services;

use Frosh\TemplateMail\Services\MailLoader\LoaderInterface;
use Shopware\Core\Framework\Adapter\Translation\Translator;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Event\BusinessEvent;
use Shopware\Core\System\Language\LanguageEntity;
use Twig\Loader\FilesystemLoader;

class MailFinderService implements MailFinderServiceInterface
{
    public const TYPE_HTML = 'html.';
    public const TYPE_PLAIN = 'plain.';
    public const TYPE_SUBJECT = 'subject.';

    /**
     * @var FilesystemLoader
     */
    private $filesystemLoader;

    /**
     * @var LoaderInterface[]
     */
    private $availableLoaders;

    /**
     * @var EntityRepositoryInterface
     */
    private $languageRepository;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var SearchPathProvider $searchPathProvider
     */
    private $searchPathProvider;

    public function __construct(
        FilesystemLoader $filesystemLoader,
        iterable $availableLoaders,
        EntityRepositoryInterface $languageRepository,
        Translator $translator,
        SearchPathProvider $searchPathProvider
    ) {
        $this->filesystemLoader = $filesystemLoader;
        $this->availableLoaders = $availableLoaders;
        $this->languageRepository = $languageRepository;
        $this->translator = $translator;
        $this->searchPathProvider = $searchPathProvider;
    }

    public function findTemplateByTechnicalName(string $type, string $technicalName, BusinessEvent $businessEvent): ?string
    {
        $paths = $this->filesystemLoader->getPaths();

        $searchFolder = $this->searchPathProvider->buildPaths($businessEvent);

        foreach ($paths as $path) {
            foreach ($this->availableLoaders as $availableLoader) {
                $supportedExtensions = $availableLoader->supportedExtensions();

                foreach ($supportedExtensions as $supportedExtension) {
                    foreach ($searchFolder as $folder) {
                        $filePath = $path . '/email/' . $folder . '/' . $technicalName . '/' . $type . $supportedExtension;
                        if (file_exists($filePath) && $content = $availableLoader->load($filePath)) {
                            $this->fixTranslator($businessEvent);

                            return $content;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function fixTranslator(BusinessEvent $businessEvent): void
    {
        if (!$businessEvent->getEvent()->getSalesChannelId()) {
            return;
        }

        $criteria = new Criteria([$businessEvent->getContext()->getLanguageId()]);
        $criteria->addAssociation('locale');

        /** @var LanguageEntity $language */
        $language = $this->languageRepository->search($criteria, $businessEvent->getContext())->first();

        $this->translator->injectSettings(
            $businessEvent->getEvent()->getSalesChannelId(),
            $businessEvent->getContext()->getLanguageId(),
            $language->getLocale()->getCode(),
            $businessEvent->getContext()
        );
    }
}
