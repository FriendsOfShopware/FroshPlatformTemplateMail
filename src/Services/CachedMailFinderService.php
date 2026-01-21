<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Frosh\TemplateMail\DTO\TemplateData;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[When('prod')]
#[AsDecorator(MailFinderService::class)]
class CachedMailFinderService implements MailFinderServiceInterface
{
    public function __construct(
        private readonly MailFinderServiceInterface $mailFinderService,
        private readonly CacheInterface $cache,
    ) {
    }

    public function getTemplateDataByTechnicalName(
        string $technicalName,
        TemplateMailContext $businessEvent,
        ?string $mailTemplateId = null,
    ): TemplateData {
        $cacheKey = hash(
            'xxh128',
            $technicalName
            . $mailTemplateId
            . $businessEvent->getSalesChannelId()
            . $businessEvent->getContext()->getLanguageId()
        );

        return $this->cache->get($cacheKey, function (ItemInterface $cacheItem) use ($technicalName, $businessEvent, $mailTemplateId) {
            $cacheItem->expiresAfter(3600);

            return $this->mailFinderService->getTemplateDataByTechnicalName($technicalName, $businessEvent, $mailTemplateId);
        });
    }
}
