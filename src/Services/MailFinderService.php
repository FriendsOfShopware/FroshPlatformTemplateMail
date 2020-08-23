<?php

namespace Frosh\TemplateMail\Services;

use Frosh\TemplateMail\Services\MailLoader\LoaderInterface;
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

    public function __construct(FilesystemLoader $filesystemLoader, iterable $availableLoaders, EntityRepositoryInterface $languageRepository)
    {
        $this->filesystemLoader = $filesystemLoader;
        $this->availableLoaders = $availableLoaders;
        $this->languageRepository = $languageRepository;
    }

    public function findTemplateByTechnicalName(string $type, string $technicalName, BusinessEvent $businessEvent): ?string
    {
        $paths = $this->filesystemLoader->getPaths();
        $searchFolder = [$businessEvent->getContext()->getLanguageId(), 'global'];

        if ($businessEvent->getContext()->getSource() instanceof Context\SalesChannelApiSource) {
            array_unshift($searchFolder, $businessEvent->getContext()->getSource()->getSalesChannelId());
        }

        if ($businessEvent->getEvent()->getSalesChannelId()) {
            array_unshift($searchFolder, $businessEvent->getEvent()->getSalesChannelId());
        }

        $criteria = new Criteria($businessEvent->getEvent()->getContext()->getLanguageIdChain());
        $criteria->addAssociation('locale');
        $languages = $this->languageRepository->search($criteria, Context::createDefaultContext())->getElements();

        /** @var LanguageEntity $language */
        foreach ($languages as $language) {
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
                            return $content;
                        }
                    }
                }
            }
        }

        return null;
    }
}
