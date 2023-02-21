<?php declare(strict_types=1);

namespace Frosh\TemplateMail;

use Frosh\TemplateMail\DependencyInjection\CacheCompilerPass;
use Shopware\Core\Framework\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class FroshPlatformTemplateMail extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CacheCompilerPass());

        parent::build($container);
    }

    public function executeComposerCommands(): bool
    {
        return true;
    }
}
