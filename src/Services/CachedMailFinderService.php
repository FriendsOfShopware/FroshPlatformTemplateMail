<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

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

    public function findTemplateByTechnicalName(
        string $type,
        string $technicalName,
        TemplateMailContext $businessEvent,
        bool $returnFolder = false,
        ?string $mailTemplateId = null,
    ): ?string {
        $salesChannelId = $businessEvent->getSalesChannelId();

        $cacheKey = hash(
            'xxh128',
            $type
            . $technicalName
            . $mailTemplateId
            . $salesChannelId
            . $businessEvent->getContext()->getLanguageId()
            . $returnFolder,
        );

        return $this->cache->get($cacheKey, function (ItemInterface $cacheItem) use ($type, $technicalName, $businessEvent, $returnFolder, $mailTemplateId) {
            $cacheItem->expiresAfter(3600);

            return $this->mailFinderService->findTemplateByTechnicalName($type, $technicalName, $businessEvent, $returnFolder, $mailTemplateId);
        });
    }
}
