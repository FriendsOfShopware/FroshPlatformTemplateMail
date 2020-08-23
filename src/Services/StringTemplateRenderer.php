<?php declare(strict_types=1);

namespace Frosh\TemplateMail\Services;

use Shopware\Core\Framework\Adapter\Twig\Exception\StringTemplateRenderingException;
use Shopware\Core\Framework\Context;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;

class StringTemplateRenderer extends \Shopware\Core\Framework\Adapter\Twig\StringTemplateRenderer
{
    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var ArrayLoader
     */
    private $arrayLoader;

    public function __construct(Environment $environment)
    {
        $this->arrayLoader = new ArrayLoader();

        // use private twig instance here, because we use custom template loader
        $this->twig = new Environment(new ChainLoader([$this->arrayLoader, $environment->getLoader()]));
        $this->twig->setCache(false);
        $this->disableTestMode();

        foreach ($environment->getExtensions() as $extension) {
            if ($this->twig->hasExtension(get_class($extension))) {
                continue;
            }
            $this->twig->addExtension($extension);
        }
    }

    /**
     * @throws StringTemplateRenderingException
     */
    public function render(string $templateSource, array $data, Context $context): string
    {
        $name = md5($templateSource);
        $this->arrayLoader->setTemplate($name, $templateSource);

        $this->twig->addGlobal('context', $context);

        try {
            return $this->twig->render($name, $data);
        } catch (Error $error) {
            throw new StringTemplateRenderingException($error->getMessage());
        }
    }

    public function enableTestMode(): void
    {
        $this->twig->addGlobal('testMode', true);
        $this->twig->disableStrictVariables();
    }

    public function disableTestMode(): void
    {
        $this->twig->addGlobal('testMode', false);
        $this->twig->enableStrictVariables();
    }
}
