<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Shopware\Core\Framework\Adapter\Twig\Exception\StringTemplateRenderingException;
use Shopware\Core\Framework\Context;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Extension\CoreExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;

// @phpstan-ignore-next-line
#[AsDecorator(\Shopware\Core\Framework\Adapter\Twig\StringTemplateRenderer::class)]
class StringTemplateRenderer extends \Shopware\Core\Framework\Adapter\Twig\StringTemplateRenderer
{
    private Environment $twig;

    private ArrayLoader $arrayLoader;

    public function __construct(private readonly Environment $platformTwig)
    {
        $this->initialize();
    }

    public function initialize(): void
    {
        $this->arrayLoader = new ArrayLoader();

        // use private twig instance here, because we use custom template loader
        $this->twig = new Environment(new ChainLoader([$this->arrayLoader, $this->platformTwig->getLoader()]));
        $this->twig->setCache(false);
        $this->disableTestMode();
        foreach ($this->platformTwig->getExtensions() as $extension) {
            if ($this->twig->hasExtension($extension::class)) {
                continue;
            }
            $this->twig->addExtension($extension);
        }

        if ($this->twig->hasExtension(CoreExtension::class) && $this->platformTwig->hasExtension(CoreExtension::class)) {
            /** @var CoreExtension $coreExtensionInternal */
            $coreExtensionInternal = $this->twig->getExtension(CoreExtension::class);
            /** @var CoreExtension $coreExtensionGlobal */
            $coreExtensionGlobal = $this->platformTwig->getExtension(CoreExtension::class);

            /** @var string|\DateTimeZone $timezone */
            $timezone = $coreExtensionGlobal->getTimezone();
            $coreExtensionInternal->setTimezone($timezone);

            /** @var array{string|null, string|null} $dateFormat */
            $dateFormat = $coreExtensionGlobal->getDateFormat();
            $coreExtensionInternal->setDateFormat(...$dateFormat);

            /** @var array{int, string, string} $numberFormat */
            $numberFormat = $coreExtensionGlobal->getNumberFormat();
            $coreExtensionInternal->setNumberFormat(...$numberFormat);
        }
    }

    public function render(string $templateSource, array $data, Context $context, bool $htmlEscape = true): string
    {
        $name = md5($templateSource);
        $this->arrayLoader->setTemplate($name, $templateSource);

        $this->twig->addGlobal('context', $context);

        try {
            return $this->twig->render($name, $data);
        } catch (Error $error) {
            // @phpstan-ignore-next-line
            throw new StringTemplateRenderingException($error->getMessage());
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function enableTestMode(): void
    {
        $this->twig->addGlobal('testMode', true);
        $this->twig->disableStrictVariables();
    }

    /**
     * @codeCoverageIgnore
     */
    public function disableTestMode(): void
    {
        $this->twig->addGlobal('testMode', false);
        $this->twig->enableStrictVariables();
    }
}
