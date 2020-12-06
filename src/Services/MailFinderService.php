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
    const TYPE_HTML = 'html.';
    const TYPE_PLAIN = 'plain.';
    const TYPE_SUBJECT = 'subject.';

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

    public function __construct(
        FilesystemLoader $filesystemLoader,
        iterable $availableLoaders,
        EntityRepositoryInterface $languageRepository,
        Translator $translator
    ) {
        $this->filesystemLoader = $filesystemLoader;
        $this->availableLoaders = $availableLoaders;
        $this->languageRepository = $languageRepository;
        $this->translator = $translator;
    }

    public function findTemplateByTechnicalName(string $type, string $technicalName, BusinessEvent $businessEvent): ?string
    {
        $paths = $this->filesystemLoader->getPaths();
        $searchFolder = [$businessEvent->getContext()->getLanguageId(), 'global'];

        if ($businessEvent->getContext()->getSource() instanceof SalesChannelApiSource) {
            array_unshift($searchFolder, $businessEvent->getContext()->getSource()->getSalesChannelId());
        }

        if ($businessEvent->getEvent()->getSalesChannelId()) {
            array_unshift($searchFolder, $businessEvent->getEvent()->getSalesChannelId());
        }

        $criteria = new Criteria($businessEvent->getEvent()->getContext()->getLanguageIdChain());
        $criteria->addAssociation('locale');
        $languages = $this->languageRepository->search($criteria, Context::createDefaultContext())->getElements();

        /** @var LanguageEntity $language */
        foreach (array_reverse($languages) as $language) {
            array_unshift($searchFolder, $language->getLocale()->getCode());
        }

        $searchFolder = array_keys(array_flip($searchFolder));

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
