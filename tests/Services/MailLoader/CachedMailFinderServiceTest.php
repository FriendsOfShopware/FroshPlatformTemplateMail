<?php
declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services\MailLoader;

use Frosh\TemplateMail\Services\TemplateMailContext;
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
        $context = new TemplateMailContext('1234', Context::createDefaultContext());

        $time = $cachedMailFinder->findTemplateByTechnicalName('', '', $context);
        static::assertSame($time, $cachedMailFinder->findTemplateByTechnicalName('', '', $context));
    }
}
