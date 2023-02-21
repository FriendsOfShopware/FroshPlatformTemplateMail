<?php declare(strict_types=1);

namespace Frosh\TemplateMail\DependencyInjection;

use Frosh\TemplateMail\Services\CachedMailFinderService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CacheCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->getParameter('kernel.environment') === 'prod') {
            return;
        }

        $container->removeDefinition(CachedMailFinderService::class);
    }
}
