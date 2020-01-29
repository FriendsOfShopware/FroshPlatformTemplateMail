<?php declare(strict_types=1);

namespace Frosh\TemplateMail;

use Frosh\TemplateMail\DependencyInjection\CacheCompilerPass;
use Frosh\TemplateMail\DependencyInjection\OrderActionControllerGeneratorPass;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use Shopware\Core\Checkout\Order\Api\OrderActionController;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerBuilder;

if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

class FroshPlatformTemplateMail extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CacheCompilerPass());
        $container->addCompilerPass(new OrderActionControllerGeneratorPass());
        parent::build($container);
    }
}
