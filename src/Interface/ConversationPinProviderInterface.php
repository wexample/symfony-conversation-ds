<?php

namespace Wexample\SymfonyConversationDs\Interface;

use Wexample\SymfonyHelpers\Entity\Interfaces\UserEntityInterface;

/**
 * Conversations an application pins at the head of the person's tabs, before
 * the ones between people: one held elsewhere — with its agent —, drawn by its
 * own chat. Tagged by being one (the bundle's extension).
 */
interface ConversationPinProviderInterface
{
    public const string TAG = 'wexample_symfony_conversation_ds.pin_provider';

    /**
     * Each pinned conversation: `key`, what the tab is opened by; `label`;
     * `avatar` ({ label?, src?, emoji? }); `unread`; `chat`, the chat drawing
     * it — a vue `component` and its `props` —, asked only of the tab open.
     *
     * @return list<array{key: string, label: string, avatar?: array, unread?: int, chat: callable(): array{component: string, props: array}}>
     */
    public function getPinnedConversations(UserEntityInterface $user): array;
}
