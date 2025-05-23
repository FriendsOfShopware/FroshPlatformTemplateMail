<?php declare(strict_types=1);

namespace Frosh\TemplateMail\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class FroshPlatformTemplateMailExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        \assert($configuration instanceof Configuration);
        /** @var array{mjml_server: string} $config */
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('frosh_platform_template_mail.mjml_server', $config['mjml_server']);
    }
}
