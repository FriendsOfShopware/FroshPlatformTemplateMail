<?php declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Frosh\TemplateMail\Event\TemplateMailBusinessEvent;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

class CachedMailFinderService implements MailFinderServiceInterface
{
    public function __construct(
        private readonly MailFinderServiceInterface $mailFinderService,
        private readonly CacheInterface $cache
    ) {
    }

    public function findTemplateByTechnicalName(
        string $type,
        string $technicalName,
        TemplateMailBusinessEvent $businessEvent,
        bool $returnFolder = false
    ): ?string {
        $salesChannelId = $businessEvent->getSalesChannelId();

        $cacheKey = md5(
            $type
            . $technicalName
            . $businessEvent->getStorableFlow()->getName()
            . json_encode($businessEvent->getStorableFlow()->getConfig(), \JSON_THROW_ON_ERROR)
            . $salesChannelId
            . $businessEvent->getContext()->getLanguageId()
        );

        return $this->cache->get($cacheKey, function (CacheItem $cacheItem) use ($type, $technicalName, $businessEvent) {
            $cacheItem->expiresAfter(3600);

            return $this->mailFinderService->findTemplateByTechnicalName($type, $technicalName, $businessEvent);
        });
    }
}
