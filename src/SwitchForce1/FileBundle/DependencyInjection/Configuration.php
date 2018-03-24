<?php

namespace SwitchForce1\FileBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('switch_f1_file');

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
            ->children()
                ->scalarNode("default_upload_dir")
                    ->cannotBeEmpty()
                    ->cannotBeOverwritten()
                    ->isRequired()
                ->end()
                ->scalarNode("default_temp_upload_dir")
                    ->cannotBeEmpty()
                    ->cannotBeOverwritten()
                    ->isRequired()
                ->end()
            ->end()
        ;
        
        return $treeBuilder;
    }
}
