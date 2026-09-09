<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\Services;

use Frosh\TemplateMail\Services\StringTemplateRenderer;
use Frosh\TemplateMail\Tests\Services\Fixtures\UppercaseExtension;
use Frosh\TemplateMail\Tests\Services\Fixtures\UppercaseRuntime;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

class StringTemplateRendererTest extends TestCase
{
    public function testRenderingWorks(): void
    {
        $renderer = new StringTemplateRenderer(new Environment(new ArrayLoader()));
        static::assertSame('foo', $renderer->render('{{ text }}', ['text' => 'foo'], Context::createDefaultContext()));
    }

    public function testRuntimeOfPlatformTwigIsUsed(): void
    {
        $platformTwig = new Environment(new ArrayLoader());
        $platformTwig->addExtension(new UppercaseExtension());
        $platformTwig->addRuntimeLoader(new FactoryRuntimeLoader([
            UppercaseRuntime::class => static fn (): UppercaseRuntime => new UppercaseRuntime(),
        ]));

        $renderer = new StringTemplateRenderer($platformTwig);

        static::assertSame('FOO', $renderer->render('{{ "foo"|uppercase }}', [], Context::createDefaultContext()));
    }

    public function testExtensionAddedAfterConstructionIsUsed(): void
    {
        $platformTwig = new Environment(new ArrayLoader());
        $renderer = new StringTemplateRenderer($platformTwig);

        // the platform twig can still be under construction when this service is created
        $platformTwig->addExtension(new UppercaseExtension());
        $platformTwig->addRuntimeLoader(new FactoryRuntimeLoader([
            UppercaseRuntime::class => static fn (): UppercaseRuntime => new UppercaseRuntime(),
        ]));

        static::assertSame('FOO', $renderer->render('{{ "foo"|uppercase }}', [], Context::createDefaultContext()));
    }

    public function testInvalidString(): void
    {
        $renderer = new StringTemplateRenderer(new Environment(new ArrayLoader()));

        if (class_exists(\Shopware\Core\Framework\Adapter\AdapterException::class) && method_exists(\Shopware\Core\Framework\Adapter\AdapterException::class, 'invalidTemplateSyntax')) {
            static::expectException(\Shopware\Core\Framework\Adapter\AdapterException::class);
        } else {
            static::expectException(\Shopware\Core\Framework\Adapter\Twig\Exception\StringTemplateRenderingException::class);
        }

        $renderer->render('{{ text() }}', [], Context::createDefaultContext());
    }
}
