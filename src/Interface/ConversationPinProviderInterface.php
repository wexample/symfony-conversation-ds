<?php

namespace Wexample\SymfonyConversationDs\Interface;

use Wexample\SymfonyHelpers\Entity\Interfaces\UserEntityInterface;

/**
 * Rows an application pins at the head of the conversations of the person
 * signed in, before the ones between people: a conversation held elsewhere —
 * the one with its agent, drawn by the agent's own chat.
 *
 * Each row as the design system's `conversation-list` takes it: `title`,
 * `href`, `preview`, `date`, `unread`, `avatar` — its `href` a page that can
 * be held in the dock's panel, read from the list and left back to it
 * (`?back=` the list's address). Tagged by being one (the bundle's
 * extension).
 */
interface ConversationPinProviderInterface
{
    public const string TAG = 'wexample_symfony_conversation_ds.pin_provider';

    /** @return list<array<string, mixed>> */
    public function getPinnedConversations(UserEntityInterface $user): array;
}
