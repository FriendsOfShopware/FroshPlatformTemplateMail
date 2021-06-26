<?php
declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\DependencyInjection;

use Frosh\TemplateMail\DependencyInjection\CacheCompilerPass;
use Frosh\TemplateMail\Services\CachedMailFinderService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class CacheCompilerPassTest extends TestCase
{
    public function testCacheGetsRemoved(): void
    {
        $c = $this->buildContainer();
        $c->setParameter('kernel.environment', 'dev');
        $c->compile();

        static::assertFalse($c->hasDefinition(CachedMailFinderService::class));
    }

    public function testCacheStaysInProd(): void
    {
        $c = $this->buildContainer();
        $c->compile();

        static::assertTrue($c->hasDefinition(CachedMailFinderService::class));
    }

    private function buildContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->addCompilerPass(new CacheCompilerPass());
        $d = new Definition();
        $d->setPublic(true);
        $container->setDefinition(CachedMailFinderService::class, $d);
        $container->setParameter('kernel.environment', 'prod');

        return $container;
    }
}
