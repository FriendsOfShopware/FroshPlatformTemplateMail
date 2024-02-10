<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services\MailLoader;

use Frosh\TemplateMail\Services\MailLoader\TwigLoader;
use PHPUnit\Framework\TestCase;
use Shopware\Storefront\Storefront;

class TwigLoaderTest extends TestCase
{
    public function testLoadingWorks(): void
    {
        $storefront = new Storefront();
        $loader = new TwigLoader();
        static::assertSame(['twig'], $loader->supportedExtensions());
        static::assertNotEmpty($loader->load($storefront->getPath() . '/Resources/views/storefront/base.html.twig'));
    }
}
