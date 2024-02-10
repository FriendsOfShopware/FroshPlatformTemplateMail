<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services;

use Frosh\TemplateMail\Services\StringTemplateRenderer;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Adapter\Twig\Exception\StringTemplateRenderingException;
use Shopware\Core\Framework\Context;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class StringTemplateRendererTest extends TestCase
{
    public function testRenderingWorks(): void
    {
        $renderer = new StringTemplateRenderer(new Environment(new ArrayLoader()));
        static::assertSame('foo', $renderer->render('{{ text }}', ['text' => 'foo'], Context::createDefaultContext()));
    }

    public function testInvalidString(): void
    {
        $renderer = new StringTemplateRenderer(new Environment(new ArrayLoader()));

        static::expectException(StringTemplateRenderingException::class);

        $renderer->render('{{ text() }}', [], Context::createDefaultContext());
    }
}
