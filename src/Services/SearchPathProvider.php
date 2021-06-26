<?php
declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Event\BusinessEvent;
use Shopware\Core\System\Language\LanguageEntity;

class SearchPathProvider
{
    /**
     * @var EntityRepositoryInterface $languageRepository
     */
    private $languageRepository;

    public function __construct(EntityRepositoryInterface $languageRepository)
    {
        $this->languageRepository = $languageRepository;
    }

    public function buildPaths(BusinessEvent $businessEvent): array
    {
        $searchFolder = [$businessEvent->getContext()->getLanguageId(), 'global'];

        if ($businessEvent->getContext()->getSource() instanceof SalesChannelApiSource) {
            array_unshift($searchFolder, $businessEvent->getContext()->getSource()->getSalesChannelId());
        }

        $criteria = new Criteria($businessEvent->getEvent()->getContext()->getLanguageIdChain());
        $criteria->addAssociation('locale');
        $languages = $this->languageRepository->search($criteria, Context::createDefaultContext())->getElements();

        /** @var LanguageEntity $language */
        foreach (array_reverse($languages) as $language) {
            array_unshift($searchFolder, $language->getLocale()->getCode());
        }

        if ($businessEvent->getEvent()->getSalesChannelId()) {
            array_unshift($searchFolder, $businessEvent->getEvent()->getSalesChannelId());
        }

        if ($businessEvent->getEvent()->getSalesChannelId()) {
            /** @var LanguageEntity $language */
            foreach (array_reverse($languages) as $language) {
                array_unshift($searchFolder, $businessEvent->getEvent()->getSalesChannelId() . '/' . $language->getLocale()->getCode());
            }
        }

        return array_keys(array_flip($searchFolder));
    }
}
