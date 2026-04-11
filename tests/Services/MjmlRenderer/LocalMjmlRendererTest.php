<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services\MjmlRenderer;

use Frosh\TemplateMail\Services\MjmlRenderer\LocalMjmlRenderer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class LocalMjmlRendererTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(\Spatie\Mjml\Mjml::class)) {
            static::markTestSkipped('spatie/mjml-php is not installed');
        }
    }

    public function testRenderingWorks(): void
    {
        $renderer = new LocalMjmlRenderer(new NullLogger());

        $mjml = file_get_contents(__DIR__ . '/../MailLoader/_fixtures/test.mjml');
        static::assertIsString($mjml);

        $html = $renderer->render($mjml);
        static::assertStringContainsString('<!doctype html>', $html);
        static::assertStringContainsString('Hello World', $html);
    }

    public function testInvalidMjmlReturnsEmptyString(): void
    {
        $renderer = new LocalMjmlRenderer(new NullLogger());

        $result = $renderer->render('this is not valid mjml at all');

        static::assertSame('', $result);
    }
}
