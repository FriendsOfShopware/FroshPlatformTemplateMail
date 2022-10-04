<?php

namespace Frosh\TemplateMail\Services;

use Frosh\TemplateMail\Services\MailLoader\LoaderInterface;
use Shopware\Core\Framework\Adapter\Translation\Translator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Event\BusinessEvent;
use Shopware\Core\Framework\Event\MailActionInterface;
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

    /**
     * @var EntityRepositoryInterface $themeRepository
     */
    private $themeRepository;

    public function __construct(
        FilesystemLoader $filesystemLoader,
        iterable $availableLoaders,
        EntityRepositoryInterface $languageRepository,
        Translator $translator,
        SearchPathProvider $searchPathProvider,
        EntityRepositoryInterface $themeRepository,
        EntityRepositoryInterface $pluginRepository
    ) {
        $this->filesystemLoader = $filesystemLoader;
        $this->availableLoaders = $availableLoaders;
        $this->languageRepository = $languageRepository;
        $this->translator = $translator;
        $this->searchPathProvider = $searchPathProvider;
        $this->themeRepository = $themeRepository;
        $this->pluginRepository = $pluginRepository;
    }

    public function findTemplateByTechnicalName(
        string $type,
        string $technicalName,
        BusinessEvent $businessEvent,
        bool $returnFolder = false
    ): ?string
    {
        $paths = $this->filesystemLoader->getPaths();

        $searchFolder = $this->searchPathProvider->buildPaths($businessEvent);

        $criteria = (new Criteria)->addFilter(new EqualsFilter('salesChannels.id', $businessEvent->getSalesChannelId()));
        $searchResult = $this->themeRepository->search($criteria, $businessEvent->getContext());

        $criteria = (new Criteria)->addFilter(new EqualsFilter('name', $searchResult->first()->getTechnicalName()));
        $searchResult = $this->pluginRepository->search($criteria, $businessEvent->getContext());
        $themePath = $searchResult->first()->getPath();

        usort($paths, function ($a, $b) use ($themePath) {
            if (strpos($a, $themePath) !== false) {
                return -1;
            }

            if (strpos($b, $themePath) !== false) {
                return 1;
            }

            return 0;
        });

        foreach ($paths as $path) {
            foreach ($this->availableLoaders as $availableLoader) {
                $supportedExtensions = $availableLoader->supportedExtensions();

                foreach ($supportedExtensions as $supportedExtension) {
                    foreach ($searchFolder as $folder) {
                        $filePath = $path . '/email/' . $folder . '/' . $technicalName . '/' . $type . $supportedExtension;
                        if (file_exists($filePath) && $content = $availableLoader->load($filePath)) {
                            if ($businessEvent->getEvent() instanceof MailActionInterface) {
                                $this->fixTranslator($businessEvent);
                            }

                            return $returnFolder ? $filePath : $content;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function fixTranslator(BusinessEvent $businessEvent): void
    {
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
