<?php

namespace Frosh\TemplateMail\DependencyInjection;

use Frosh\TemplateMail\Event\MailDataBagFilter;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Shopware\Core\Content\MailTemplate\Subscriber\MailSendSubscriber;
use Shopware\Core\Content\MailTemplate\Subscriber\MailSendSubscriberConfig;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MailSendSubscriberGeneratorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $mailAction = new \ReflectionClass(MailSendSubscriber::class);

        $builder = new BuilderFactory();

        $phpParser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $mailSendNodes = $phpParser->parse(file_get_contents($mailAction->getFileName()));

        $nodeFinder = new NodeFinder();
        /** @var Namespace_ $namespace */

        $namespace = $nodeFinder->findFirstInstanceOf($mailSendNodes, Namespace_::class);
        $namespace->name = new Name(__NAMESPACE__);

        array_unshift($namespace->stmts, $builder->use('\\' . MailSendSubscriberConfig::class)->getNode());

        /** @var Class_ $class */
        $class = $nodeFinder->findFirstInstanceOf($mailSendNodes, Class_::class);
        $class->extends = new Name('\\' . MailSendSubscriber::class);

        // EventDispatcher property
        array_unshift($class->stmts, $builder->property('froshEventDispatcher')->getNode());

        $methods = $nodeFinder->findInstanceOf($class->stmts, ClassMethod::class);

        $propertyFetch = $builder->propertyFetch($builder->var('this'), 'froshEventDispatcher');
        $argCount = null;

        /** @var ClassMethod $method */
        foreach ($methods as $method) {
            if ($method->name->name === '__construct') {
                $method->params[] = $builder->param('froshEventDispatcher')->getNode();
                $argCount = count($method->params);
                $method->stmts[] = new Expression(new Assign($propertyFetch, $builder->var('froshEventDispatcher')));
            } else if($method->name->name === 'sendMail') {
                foreach ($method->stmts as $i => $stmt) {
                    if (!$stmt instanceof Expression) {
                        continue;
                    }

                    if (!$stmt->expr instanceof MethodCall) {
                        continue;
                    }

                    if ($stmt->expr->name->name !== 'set') {
                        continue;
                    }

                    if (! $stmt->expr->var instanceof Variable) {
                        continue;
                    }

                    if (!$stmt->expr->var->name === 'data') {
                        continue;
                    }

                    if (!isset($stmt->expr->args[0])) {
                        continue;
                    }

                    if (! $stmt->expr->args[0]->value instanceof String_) {
                        continue;
                    }

                    if ($stmt->expr->args[0]->value->value !== 'mediaIds') {
                        continue;
                    }

                    // Fire event
                    $arg = $builder->new('\\' . MailDataBagFilter::class, [$builder->var('data'), $builder->var('mailTemplate'), $builder->methodCall($builder->var('event'), 'getContext')]);
                    $newStmt = new Expression($builder->methodCall($propertyFetch, 'dispatch', [$arg]));

                    array_splice($method->stmts, $i, 0, [$newStmt]);
                }
            }
        }

        $printer = new Standard();

        file_put_contents(__DIR__ . '/MailSendSubscriber.php', $printer->prettyPrintFile($mailSendNodes));

        $container->getDefinition(MailSendSubscriber::class)
            ->setClass(__NAMESPACE__ . '\\MailSendSubscriber')
            ->setArgument($argCount - 1, new Reference(EventDispatcherInterface::class));
    }
}
