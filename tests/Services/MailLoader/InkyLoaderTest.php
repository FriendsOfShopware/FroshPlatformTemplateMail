<?php
declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services\MailLoader;

use Frosh\TemplateMail\Services\MailLoader\InkyLoader;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class InkyLoaderTest extends TestCase
{
    public function testLoadingWorks(): void
    {
        if (!extension_loaded('xsl')) {
            static::markTestSkipped('XSL extension is required');
        }

        $loader = new InkyLoader(new NullLogger());
        static::assertSame(['inky.html'], $loader->supportedExtensions());

        $text = $loader->load(__DIR__ . '/_fixtures/test.inky');
        static::assertStringContainsString('<table align', $text);
    }
}
