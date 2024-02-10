<?php

namespace Frosh\TemplateMail\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class FroshPlatformTemplateMailExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);
        $container->setParameter('frosh_platform_template_mail.mjml_server', $config['mjml_server']);
    }
}
