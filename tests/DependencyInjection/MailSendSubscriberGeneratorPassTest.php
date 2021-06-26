<?php
declare(strict_types=1);

namespace Frosh\TemplateMail\Tests\DependencyInjection;

use Frosh\TemplateMail\DependencyInjection\MailSendSubscriberGeneratorPass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\MailTemplate\Subscriber\MailSendSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MailSendSubscriberGeneratorPassTest extends TestCase
{
    public function testBuilds(): void
    {
        $c = new ContainerBuilder();
        $d = new Definition(MailSendSubscriber::class);

        $r = new \ReflectionClass(MailSendSubscriber::class);

        foreach ($r->getConstructor()->getParameters() as $parameter) {
            $d->addArgument($parameter->getName());
        }

        $d->setPublic(true);
        $c->setDefinition(MailSendSubscriber::class, $d);

        $c->setDefinition(EventDispatcherInterface::class, new Definition(EventDispatcher::class));
        $c->addCompilerPass(new MailSendSubscriberGeneratorPass());
        $c->compile();

        $r = new \ReflectionClass(MailSendSubscriberGeneratorPass::class);

        static::assertFileExists(dirname($r->getFileName()) . '/MailSendSubscriber.php');
        static::assertNotSame(MailSendSubscriber::class, $d->getClass());
    }
}
