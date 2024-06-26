<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services;

use Frosh\TemplateMail\Services\TemplateMailContext;
use Frosh\TemplateMail\Services\SearchPathProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\Test\TestDefaults;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;

class SearchPathProviderTest extends TestCase
{
    /**
     * @param array<string> $expectedPaths
     * @dataProvider eventProvider
     */
    public function testPaths(TemplateMailContext $event, array $expectedPaths): void
    {
        $language = new LanguageEntity();
        $language->setId(Defaults::LANGUAGE_SYSTEM);
        $locale = new LocaleEntity();
        $locale->setCode('en-GB');
        $language->setLocale($locale);
        $repository = new StaticEntityRepository([new LanguageCollection([$language])]);

        // @phpstan-ignore-next-line
        $provider = new SearchPathProvider($repository);
        static::assertSame($expectedPaths, $provider->buildPaths($event));
    }

    public static function eventProvider(): \Generator
    {
        // Without sales channel source

        yield [
            self::createEvent(),
            [
                '98432def39fc4624b33213a56b8c944d/en-GB', // Sales channel and language combo
                '98432def39fc4624b33213a56b8c944d', // Sales channel
                'en-GB', // Language code
                '2fbb5fe2e29a4d70aa5854ce7ce3e20b', // Language id
                'global', // Global
            ],
        ];

        // With sales channel source

        yield [
            self::createEvent(true),
            [
                '98432def39fc4624b33213a56b8c944d/en-GB', // Sales channel and language combo
                '98432def39fc4624b33213a56b8c944d', // Sales channel
                'en-GB', // Language code
                '2fbb5fe2e29a4d70aa5854ce7ce3e20b', // Language id
                'global', // Global
            ],
        ];
    }

    private static function createEvent(bool $salesChannelSource = false): TemplateMailContext
    {
        $context = new Context(
            $salesChannelSource ? new SalesChannelApiSource(TestDefaults::SALES_CHANNEL) : new SystemSource(),
        );

        return new TemplateMailContext(TestDefaults::SALES_CHANNEL, $context);
    }
}
