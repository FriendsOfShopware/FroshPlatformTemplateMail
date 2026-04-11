<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services\MailLoader;

use Frosh\TemplateMail\Exception\MjmlCompileError;
use Frosh\TemplateMail\Services\MailLoader\MjmlLoader;
use Frosh\TemplateMail\Services\MjmlRenderer\MjmlRendererInterface;
use PHPUnit\Framework\TestCase;

class MjmlLoaderTest extends TestCase
{
    public function testSupportedExtensions(): void
    {
        $renderer = $this->createMock(MjmlRendererInterface::class);
        $loader = new MjmlLoader($renderer);

        static::assertSame(['mjml'], $loader->supportedExtensions());
    }

    public function testLoadDelegatesToRenderer(): void
    {
        $renderer = $this->createMock(MjmlRendererInterface::class);
        $renderer->expects(static::once())
            ->method('render')
            ->with(static::stringContains('<mjml>'))
            ->willReturn('<html>rendered</html>');

        $loader = new MjmlLoader($renderer);
        $result = $loader->load(__DIR__ . '/_fixtures/test.mjml');

        static::assertSame('<html>rendered</html>', $result);
    }

    public function testLoadReturnsEmptyStringForNonExistentFile(): void
    {
        $renderer = $this->createMock(MjmlRendererInterface::class);
        $renderer->expects(static::never())->method('render');

        $loader = new MjmlLoader($renderer);
        $result = $loader->load(__DIR__ . '/_fixtures/nonexistent.mjml');

        static::assertSame('', $result);
    }

    public function testLoadThrowsOnMissingInclude(): void
    {
        $renderer = $this->createMock(MjmlRendererInterface::class);
        $loader = new MjmlLoader($renderer);

        // Create a temp file with an include that doesn't exist
        $tempDir = sys_get_temp_dir() . '/mjml_test_' . uniqid();
        mkdir($tempDir);
        file_put_contents($tempDir . '/test.mjml', '<mjml><mj-body><mj-include path="missing.mjml" /></mj-body></mjml>');

        static::expectException(MjmlCompileError::class);

        try {
            $loader->load($tempDir . '/test.mjml');
        } finally {
            unlink($tempDir . '/test.mjml');
            rmdir($tempDir);
        }
    }
}
