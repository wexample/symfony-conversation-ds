<?php

namespace Wexample\SymfonyConversationDs\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyConversationDs\Interface\ConversationPinProviderInterface;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;

class WexampleSymfonyConversationDsExtension extends AbstractWexampleSymfonyExtension
{
    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        // What an application pins at the head of the conversations: found
        // by being one.
        $container->registerForAutoconfiguration(ConversationPinProviderInterface::class)
            ->addTag(ConversationPinProviderInterface::TAG);

        $this->loadConfig(
            __DIR__,
            $container
        );
    }
}
