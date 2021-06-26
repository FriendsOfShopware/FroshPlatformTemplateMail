<?php
declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services;

use Frosh\TemplateMail\Services\SearchPathProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\ContactForm\Event\ContactFormEvent;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Event\BusinessEvent;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;

class SearchPathProviderTest extends TestCase
{
    /**
     * @dataProvider eventProvider
     */
    public function testPaths(BusinessEvent $event, array $expectedPaths): void
    {
        $repository = $this->createMock(EntityRepository::class);

        $language = new LanguageEntity();
        $language->setId(Defaults::LANGUAGE_SYSTEM);
        $locale = new LocaleEntity();
        $locale->setCode('en-GB');
        $language->setLocale($locale);
        $repository->method('search')->willReturn(new EntitySearchResult('language', 1, new EntityCollection([$language]), null, new Criteria(), Context::createDefaultContext()));

        $provider = new SearchPathProvider($repository);
        static::assertSame($expectedPaths, $provider->buildPaths($event));
    }

    public function eventProvider(): iterable
    {
        // Without sales channel source

        yield [
            $this->createEvent(),
            [
                '98432def39fc4624b33213a56b8c944d/en-GB', // Sales channel and language combo
                '98432def39fc4624b33213a56b8c944d', // Sales channel
                'en-GB', // Language code
                '2fbb5fe2e29a4d70aa5854ce7ce3e20b', // Language id
                'global' // Global
            ]
        ];

        // With sales channel source

        yield [
            $this->createEvent(true),
            [
                '98432def39fc4624b33213a56b8c944d/en-GB', // Sales channel and language combo
                '98432def39fc4624b33213a56b8c944d', // Sales channel
                'en-GB', // Language code
                '2fbb5fe2e29a4d70aa5854ce7ce3e20b', // Language id
                'global' // Global
            ]
        ];
    }

    private function createEvent(bool $salesChannelSource = false): BusinessEvent
    {
        $context = new Context(
            $salesChannelSource ? new SalesChannelApiSource(Defaults::SALES_CHANNEL) : new SystemSource()
        );

        $innerEvent = new ContactFormEvent($context, Defaults::SALES_CHANNEL, new MailRecipientStruct([]), new DataBag());

        return new BusinessEvent('mail', $innerEvent, []);
    }

}
