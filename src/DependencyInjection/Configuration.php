<?php declare(strict_types=1);

namespace Frosh\TemplateMail\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('frosh_platform_template_mail');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->scalarNode('mjml_server')->defaultValue('https://mjml.shyim.de')->end()
                ->enumNode('mjml_renderer')
                    ->values(['api', 'local'])
                    ->defaultValue('api')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
