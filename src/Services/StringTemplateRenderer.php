<?php

declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Shopware\Core\Framework\Context;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\CoreExtension;
use Twig\Extension\EscaperExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

// @phpstan-ignore-next-line
#[AsDecorator(\Shopware\Core\Framework\Adapter\Twig\StringTemplateRenderer::class)]
class StringTemplateRenderer extends \Shopware\Core\Framework\Adapter\Twig\StringTemplateRenderer
{
    private Environment $twig;

    private ArrayLoader $arrayLoader;

    private bool $extensionsCopied = false;

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
        $this->extensionsCopied = false;

        // filters and functions declared with #[AsTwigFilter] / #[AsTwigFunction] are called on a
        // runtime object, which can only be resolved by the runtime loaders of the platform twig
        $this->twig->addRuntimeLoader(new class($this->platformTwig) implements RuntimeLoaderInterface {
            public function __construct(private readonly Environment $platformTwig)
            {
            }

            /**
             * @param class-string $class
             */
            public function load(string $class): ?object
            {
                try {
                    return $this->platformTwig->getRuntime($class);
                } catch (RuntimeError) {
                    return null;
                }
            }
        });
    }

    /**
     * The extensions are copied lazily: this service can be built while the platform twig is still
     * being constructed (circular reference through a twig extension), and extensions registered
     * after that point - like the AttributeExtension instances, which are always added last - would
     * silently be missing from an eager copy.
     */
    private function copyExtensions(): void
    {
        if ($this->extensionsCopied) {
            return;
        }

        $this->extensionsCopied = true;

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
        $this->copyExtensions();

        $name = md5($templateSource);
        $this->arrayLoader->setTemplate($name, $templateSource);

        $this->twig->addGlobal('context', $context);

        if ($this->twig->hasExtension(EscaperExtension::class)) {
            /** @var EscaperExtension $escaperExtension */
            $escaperExtension = $this->twig->getExtension(EscaperExtension::class);
            $escaperExtension->setDefaultStrategy($htmlEscape ? 'html' : false);
        }

        if ($this->twig->hasExtension(CoreExtension::class) && \array_key_exists('timezone', $data) && $data['timezone'] !== null) {
            $coreExtension = $this->twig->getExtension(CoreExtension::class);
            $timezone = $data['timezone'];

            if (\is_string($timezone) || $timezone instanceof \DateTimeZone) {
                $coreExtension->setTimezone($timezone);
            }
        }

        try {
            return $this->twig->render($name, $data);
        } catch (Error $error) {
            // @phpstan-ignore-next-line
            if (class_exists(\Shopware\Core\Framework\Adapter\AdapterException::class) && method_exists(\Shopware\Core\Framework\Adapter\AdapterException::class, 'invalidTemplateSyntax')) {
                if ($error instanceof SyntaxError) {
                    throw \Shopware\Core\Framework\Adapter\AdapterException::invalidTemplateSyntax($error->getMessage());
                }

                throw \Shopware\Core\Framework\Adapter\AdapterException::renderingTemplateFailed($error->getMessage());
            } else {
                // @phpstan-ignore-next-line
                throw new \Shopware\Core\Framework\Adapter\Twig\Exception\StringTemplateRenderingException($error->getMessage());
            }
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
