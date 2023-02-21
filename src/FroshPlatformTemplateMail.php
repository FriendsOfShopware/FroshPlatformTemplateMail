<?php declare(strict_types=1);

namespace Frosh\TemplateMail;

use Frosh\TemplateMail\DependencyInjection\CacheCompilerPass;
use PHPUnit\Framework\Attributes\CodeCoverageIgnore;
use Shopware\Core\Framework\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

// @codeCoverageIgnoreStart
if (file_exists(\dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once \dirname(__DIR__) . '/vendor/autoload.php';
}
// @codeCoverageIgnoreEnd
#[CodeCoverageIgnore]
class FroshPlatformTemplateMail extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CacheCompilerPass());

        parent::build($container);
    }
}
