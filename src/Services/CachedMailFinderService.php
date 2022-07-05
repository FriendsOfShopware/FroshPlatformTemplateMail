<?php

namespace Frosh\TemplateMail\Services;

use Shopware\Core\Framework\Event\BusinessEvent;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

class CachedMailFinderService implements MailFinderServiceInterface
{
    /**
     * @var MailFinderServiceInterface
     */
    private $mailFinderService;

    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(MailFinderServiceInterface $mailFinderService, CacheInterface $cache)
    {
        $this->mailFinderService = $mailFinderService;
        $this->cache = $cache;
    }

    public function findTemplateByTechnicalName(
        string $type,
        string $technicalName,
        BusinessEvent $businessEvent,
        bool $returnFolder = false
    ): ?string
    {
        $salesChannelId = '';

        if (method_exists($businessEvent, 'getSalesChannelId')) {
            $salesChannelId = $businessEvent->getSalesChannelId();
        }

        $cacheKey = md5(
            $type .
            $technicalName .
            $businessEvent->getName() .
            json_encode($businessEvent->getConfig()) .
            $salesChannelId .
            $businessEvent->getContext()->getLanguageId()
        );
        return $this->cache->get($cacheKey, function (CacheItem $cacheItem) use ($type, $technicalName, $businessEvent) {
            $cacheItem->expiresAfter(3600);
            return $this->mailFinderService->findTemplateByTechnicalName($type, $technicalName, $businessEvent);
        });
    }
}
