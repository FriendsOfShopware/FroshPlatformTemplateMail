<?php

namespace Frosh\TemplateMail\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CacheCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->getParameter('kernel.environment') === 'prod') {
            return;
        }

        $container->removeDefinition('Frosh\TemplateMail\Services\CachedMailFinderService');
    }
}
