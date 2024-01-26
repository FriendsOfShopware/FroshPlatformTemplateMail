<?php declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Frosh\TemplateMail\Services\TemplateMailContext;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Contracts\Cache\CacheInterface;

#[When('prod')]
#[AsDecorator(MailFinderService::class)]
readonly class CachedMailFinderService implements MailFinderServiceInterface
{
    public function __construct(
        private MailFinderServiceInterface $mailFinderService,
        private CacheInterface $cache
    ) {
    }

    public function findTemplateByTechnicalName(
        string $type,
        string $technicalName,
        TemplateMailContext $businessEvent,
        bool $returnFolder = false
    ): ?string {
        $salesChannelId = $businessEvent->getSalesChannelId();

        $cacheKey = hash('xxh128',
            $type
            . $technicalName
            . $salesChannelId
            . $businessEvent->getContext()->getLanguageId()
            . $returnFolder
        );

        return $this->cache->get($cacheKey, function (CacheItem $cacheItem) use ($type, $technicalName, $businessEvent, $returnFolder) {
            $cacheItem->expiresAfter(3600);

            return $this->mailFinderService->findTemplateByTechnicalName($type, $technicalName, $businessEvent, $returnFolder);
        });
    }
}
