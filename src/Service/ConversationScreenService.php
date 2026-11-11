<?php

namespace Wexample\SymfonyConversationDs\Service;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyConversation\Api\Normalizer\Entity\ConversationParticipant\DefaultParticipantNormalizer;
use Wexample\SymfonyConversation\Entity\Conversation;
use Wexample\SymfonyConversation\Entity\ConversationParticipant;
use Wexample\SymfonyConversation\Interface\ConversationPersonPresenterInterface;
use Wexample\SymfonyConversation\Repository\ConversationRepository;
use Wexample\SymfonyConversation\Repository\MessageRepository;
use Wexample\SymfonyConversation\Repository\UserParticipantRepository;
use Wexample\SymfonyConversationDs\Controller\Pages\ConversationController;
use Wexample\SymfonyConversationDs\Interface\ConversationPinProviderInterface;
use Wexample\SymfonyHelpers\Entity\Interfaces\UserEntityInterface;

/**
 * What the conversation screens draw for the person signed in: their
 * conversations between people as the design system's `conversation-list`
 * takes them, one of them as its chat takes it.
 *
 * A conversation an engine holds — a subclass, with an agent — is drawn by
 * the engine's own chat, where it lives; these screens hold the conversations
 * between people, and the rows the application pins before them
 * (ConversationPinProviderInterface): its agent's.
 */
final readonly class ConversationScreenService
{
    public function __construct(
        private ConversationRepository $conversations,
        private UserParticipantRepository $userParticipants,
        private MessageRepository $messages,
        private ConversationPersonPresenterInterface $presenter,
        private DefaultParticipantNormalizer $participantNormalizer,
        private UrlGeneratorInterface $urlGenerator,
        /** @var iterable<ConversationPinProviderInterface> */
        #[AutowireIterator(ConversationPinProviderInterface::TAG)]
        private iterable $pinProviders,
    ) {
    }

    public static function isBetweenPeople(Conversation $conversation): bool
    {
        return Conversation::class === ClassHelper::getRealClassPath($conversation);
    }

    /**
     * Their conversations, the one that last spoke first, as rows.
     *
     * @return list<array<string, mixed>>
     */
    public function listItems(UserEntityInterface $user, ?Conversation $active = null): array
    {
        $items = [];

        // What the application pins first: its agent's conversation.
        foreach ($this->pinProviders as $provider) {
            array_push($items, ...$provider->getPinnedConversations($user));
        }

        foreach ($this->conversations->queryForUser($user)->getQuery()->getResult() as $conversation) {
            if (! self::isBetweenPeople($conversation)) {
                continue;
            }

            $me = $this->userParticipants->findOneByConversationAndUser($conversation, $user);
            $others = array_values(array_filter(
                $conversation->getParticipants()->toArray(),
                fn ($participant) => $participant !== $me
            ));
            $last = $this->messages->findOneBy(['conversation' => $conversation], ['dateCreated' => 'DESC', 'id' => 'DESC']);

            $items[] = [
                'title' => $this->titleOf($conversation, $others),
                'href' => $this->urlGenerator->generate(ConversationController::ROUTE_SHOW, ['id' => $conversation->getId()]),
                'preview' => $last?->getBody(),
                'date' => $conversation->getDateLastMessage() ?? $conversation->getDateCreated(),
                'unread' => $me ? $this->messages->countUnreadFor($me) : 0,
                'active' => $active && $active->getId()->equals($conversation->getId()),
                ...(1 === count($others)
                    ? ['avatar' => $this->face($others[0])]
                    : ['people' => array_map(fn ($participant) => $this->face($participant), $others)]),
            ];
        }

        return $items;
    }

    /**
     * One conversation as its chat takes it: who takes part, who the reader
     * is among them, what they last read — read before the chat marks it.
     *
     * @return array{conversationId: string, me: ?string, people: list<array>, unreadSince: ?string}
     */
    public function chatProps(Conversation $conversation, UserEntityInterface $user): array
    {
        $me = $this->userParticipants->findOneByConversationAndUser($conversation, $user);

        return [
            'conversationId' => (string) $conversation->getId(),
            'me' => $me ? (string) $me->getId() : null,
            'people' => array_values(array_map(
                fn ($participant) => $this->participantNormalizer->flatten($participant),
                $conversation->getParticipants()->toArray()
            )),
            'unreadSince' => $me?->getDateLastRead()?->format(DATE_ATOM),
        ];
    }

    /** What it is called: its title, else the people the reader talks with. */
    public function titleOf(Conversation $conversation, array $others): string
    {
        return $conversation->getTitle()
            ?: implode(', ', array_map(fn ($participant) => $participant->getLabel(), $others));
    }

    /**
     * The faces of some participants, as the design system's avatar-group takes them.
     *
     * @param ConversationParticipant[] $participants
     * @return list<array{label: string, src: ?string, emoji: ?string}>
     */
    public function facesOf(array $participants): array
    {
        return array_values(array_map(fn (ConversationParticipant $participant) => $this->face($participant), $participants));
    }

    /** @return array{label: string, src: ?string, emoji: ?string} */
    private function face(ConversationParticipant $participant): array
    {
        $person = $this->presenter->present($participant);

        return ['label' => $person['label'], 'src' => $person['src'], 'emoji' => $person['emoji']];
    }
}
