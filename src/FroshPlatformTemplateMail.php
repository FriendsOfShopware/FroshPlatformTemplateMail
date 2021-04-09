<?php declare(strict_types=1);

namespace Frosh\TemplateMail;

use Frosh\TemplateMail\DependencyInjection\CacheCompilerPass;
use Frosh\TemplateMail\DependencyInjection\MailSendSubscriberGeneratorPass;
use Frosh\TemplateMail\DependencyInjection\OrderServiceGeneratorPass;
use Shopware\Core\Framework\Plugin;
use Symfony\Component\DependencyInjection\ContainerBuilder;

if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

class FroshPlatformTemplateMail extends Plugin
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CacheCompilerPass());
        $container->addCompilerPass(new MailSendSubscriberGeneratorPass());
        $container->addCompilerPass(new OrderServiceGeneratorPass());

        parent::build($container);
    }
}
