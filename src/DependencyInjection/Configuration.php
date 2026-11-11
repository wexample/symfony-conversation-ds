<?php

namespace Wexample\SymfonyConversationDs\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wexample_symfony_conversation_ds');

        $treeBuilder->getRootNode()
            ->children()
            // Whether the people of the application write to each other
            // here: off, the screen holds what the application pins — its
            // agent — and nothing to start a conversation with.
            ->booleanNode('people')->defaultTrue()->end()
            ->end();

        return $treeBuilder;
    }
}
