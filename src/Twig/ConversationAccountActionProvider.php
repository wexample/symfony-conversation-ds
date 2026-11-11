<?php

namespace Wexample\SymfonyConversationDs\Twig;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Wexample\SymfonyConversationDs\Controller\Pages\ConversationController;
use Wexample\SymfonyConversationDs\Service\ConversationScreenService;
use Wexample\SymfonyHelpers\Entity\Interfaces\UserEntityInterface;
use Wexample\SymfonyUserDs\Interface\AccountActionProviderInterface;

/**
 * Writing to the holder of an account, from its page: the conversation of the
 * two, opened if they hold none. Not to oneself, nor where people do not
 * write to each other.
 */
final readonly class ConversationAccountActionProvider implements AccountActionProviderInterface
{
    public function __construct(
        private Security $security,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
        private ConversationScreenService $screen,
    ) {
    }

    public function getAccountActions(array $account): array
    {
        $user = $this->security->getUser();

        if (! $this->screen->hasPeople() || ! $user instanceof UserEntityInterface || (string) $user->getId() === (string) $account['id']) {
            return [];
        }

        return [[
            'icon' => 'ph:bold/chat-circle-text',
            'label' => $this->translator->trans('WexampleSymfonyConversationDsBundle.common.conversation::action.write'),
            'href' => $this->urlGenerator->generate(ConversationController::ROUTE_WITH, ['user' => $account['id']]),
        ]];
    }
}
