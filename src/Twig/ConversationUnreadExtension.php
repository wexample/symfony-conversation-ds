<?php

namespace Wexample\SymfonyConversationDs\Twig;

use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Wexample\SymfonyConversation\Service\ConversationUnreadService;
use Wexample\SymfonyHelpers\Entity\Interfaces\UserEntityInterface;

/**
 * How many lines wait for the person signed in, all conversations together:
 * what the way to them shows before anything is heard live
 * (components/conversation-unread).
 */
final class ConversationUnreadExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly ConversationUnreadService $unread,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('conversation_unread_count', $this->count(...)),
        ];
    }

    public function count(): int
    {
        $user = $this->security->getUser();

        return $user instanceof UserEntityInterface ? $this->unread->countFor($user) : 0;
    }
}
