<?php
declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\DependencyInjection;

use Frosh\TemplateMail\DependencyInjection\OrderServiceGeneratorPass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OrderServiceGeneratorPassTest extends TestCase
{
    public function testBuilds(): void
    {
        $c = new ContainerBuilder();
        $d = new Definition(OrderService::class);

        $r = new \ReflectionClass(OrderService::class);

        foreach ($r->getConstructor()->getParameters() as $parameter) {
            $d->addArgument($parameter->getName());
        }

        $d->setPublic(true);
        $c->setDefinition(OrderService::class, $d);

        $c->setDefinition(EventDispatcherInterface::class, new Definition(EventDispatcher::class));
        $c->addCompilerPass(new OrderServiceGeneratorPass());
        $c->compile();

        $r = new \ReflectionClass(OrderServiceGeneratorPass::class);

        static::assertFileExists(dirname($r->getFileName()) . '/OrderService.php');
        static::assertNotSame(OrderService::class, $d->getClass());
    }
}
