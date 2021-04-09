<?php

namespace Frosh\TemplateMail\DependencyInjection;

use Frosh\TemplateMail\Event\MailDataBagFilter;
use PhpParser\BuilderFactory;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OrderServiceGeneratorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $orderAction = new \ReflectionClass(OrderService::class);

        $builder = new BuilderFactory();

        $phpParser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $orderActionNodes = $phpParser->parse(file_get_contents($orderAction->getFileName()));

        $nodeFinder = new NodeFinder();
        /** @var Namespace_ $namespace */
        $namespace = $nodeFinder->findFirstInstanceOf($orderActionNodes, Namespace_::class);
        $namespace->name = new Name(__NAMESPACE__);

        /** @var Class_ $class */
        $class = $nodeFinder->findFirstInstanceOf($orderActionNodes, Class_::class);
        $class->extends = new Name('\\' . OrderService::class);

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

                    if ($stmt->expr->name->name !== 'send') {
                        continue;
                    }

                    if (! $stmt->expr->var instanceof PropertyFetch) {
                        continue;
                    }

                    if (!$stmt->expr->var->name->name === 'mailService') {
                        continue;
                    }

                    // Fetch order context to pass it into the event
                    $orderContextStmt = new Expression(new Assign($builder->var('orderContext'), $builder->methodCall($builder->var('this'), 'getOrderContext', [$builder->var('context'), $builder->var('order')])));
                    // Fire event
                    $arg = $builder->new('\\' . MailDataBagFilter::class, [$builder->var('data'), $builder->var('mailTemplate'), $builder->var('orderContext')]);
                    $newStmt = new Expression($builder->methodCall($propertyFetch, 'dispatch', [$arg]));

                    array_splice($method->stmts, $i, 0, [$orderContextStmt, $newStmt]);
                }
            }
        }

        $printer = new Standard();

        file_put_contents(__DIR__ . '/OrderService.php', $printer->prettyPrintFile($orderActionNodes));

        $container->getDefinition(OrderService::class)
            ->setClass(__NAMESPACE__ . '\\OrderService')
            ->setArgument($argCount - 1, new Reference(EventDispatcherInterface::class));
    }
}
