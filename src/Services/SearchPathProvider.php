<?php declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Frosh\TemplateMail\Services\TemplateMailContext;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\Language\LanguageEntity;

class SearchPathProvider
{
    public function __construct(private readonly EntityRepository $languageRepository)
    {
    }

    public function buildPaths(TemplateMailContext $businessEvent): array
    {
        $searchFolder = [$businessEvent->getContext()->getLanguageId(), 'global'];

        if ($businessEvent->getContext()->getSource() instanceof SalesChannelApiSource) {
            array_unshift($searchFolder, $businessEvent->getContext()->getSource()->getSalesChannelId());
        }

        $criteria = new Criteria($businessEvent->getContext()->getLanguageIdChain());
        $criteria->addAssociation('locale');
        $languages = $this->languageRepository->search($criteria, Context::createDefaultContext())->getElements();

        /** @var LanguageEntity $language */
        foreach (array_reverse($languages) as $language) {
            $localeCode = $language->getLocale()?->getCode();
            if ($localeCode === null) {
                continue;
            }

            array_unshift($searchFolder, $localeCode);
        }

        $salesChannelId = $businessEvent->getSalesChannelId();

        if ($salesChannelId !== '' && $salesChannelId !== '0') {
            array_unshift($searchFolder, $salesChannelId);
        }

        if ($salesChannelId !== '' && $salesChannelId !== '0') {
            /** @var LanguageEntity $language */
            foreach (array_reverse($languages) as $language) {
                array_unshift($searchFolder, $salesChannelId . '/' . $language->getLocale()?->getCode());
            }
        }

        return array_keys(array_flip($searchFolder));
    }
}
