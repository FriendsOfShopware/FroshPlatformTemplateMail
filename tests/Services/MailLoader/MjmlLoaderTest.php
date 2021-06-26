<?php
declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services\MailLoader;

use Frosh\TemplateMail\Services\MailLoader\MjmlLoader;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class MjmlLoaderTest extends TestCase
{
    public function testLoadingWorks(): void
    {
        $loader = new MjmlLoader(new NullLogger());
        static::assertSame(['mjml'], $loader->supportedExtensions());

        $text = $loader->load(__DIR__ . '/_fixtures/test.mjml');
        static::assertStringContainsString('<!doctype html>', $text);
        static::assertStringContainsString('<tbody>', $text);
    }
}
