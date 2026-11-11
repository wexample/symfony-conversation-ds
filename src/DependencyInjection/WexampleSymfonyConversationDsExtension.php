<?php

namespace Wexample\SymfonyConversationDs\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyConversationDs\Interface\ConversationPinProviderInterface;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;

class WexampleSymfonyConversationDsExtension extends AbstractWexampleSymfonyExtension
{
    final public const string PARAMETER_PEOPLE = 'wexample_symfony_conversation_ds.people';

    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter(self::PARAMETER_PEOPLE, $config['people']);

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
