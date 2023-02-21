<?php
declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services\MailLoader;

use Frosh\TemplateMail\Event\TemplateMailBusinessEvent;
use Frosh\TemplateMail\Services\CachedMailFinderService;
use Frosh\TemplateMail\Services\MailFinderService;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class CachedMailFinderServiceTest extends TestCase
{
    public function testCachingWorks(): void
    {
        $mailFinder = $this->createMock(MailFinderService::class);
        $mailFinder->method('findTemplateByTechnicalName')->willReturnCallback(fn () => (string) microtime(true));

        $cachedMailFinder = new CachedMailFinderService($mailFinder, new ArrayAdapter());
        $event = $this->createMock(TemplateMailBusinessEvent::class);
        $event->method('getName')->willReturn('foo');
        $event->method('getSalesChannelId')->willReturn('foo');
        $event->method('getContext')->willReturn(Context::createDefaultContext());
        $event->method('getConfig')->willReturn([]);

        $time = $cachedMailFinder->findTemplateByTechnicalName('', '', $event);
        static::assertSame($time, $cachedMailFinder->findTemplateByTechnicalName('', '', $event));
    }
}
