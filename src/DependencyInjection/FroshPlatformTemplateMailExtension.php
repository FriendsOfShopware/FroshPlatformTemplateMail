<?php declare(strict_types=1);

namespace Frosh\TemplateMail\DependencyInjection;

use Frosh\TemplateMail\Services\MjmlRenderer\ApiMjmlRenderer;
use Frosh\TemplateMail\Services\MjmlRenderer\LocalMjmlRenderer;
use Frosh\TemplateMail\Services\MjmlRenderer\MjmlRendererInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class FroshPlatformTemplateMailExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        \assert($configuration instanceof Configuration);
        /** @var array{mjml_server: string, mjml_renderer: string} $config */
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('frosh_platform_template_mail.mjml_server', $config['mjml_server']);

        $rendererClass = match ($config['mjml_renderer']) {
            'local' => LocalMjmlRenderer::class,
            default => ApiMjmlRenderer::class,
        };

        $container->setAlias(MjmlRendererInterface::class, $rendererClass);
    }
}
