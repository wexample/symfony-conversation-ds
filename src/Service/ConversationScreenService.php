<?php

namespace Wexample\SymfonyConversationDs\Service;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyConversation\Api\Normalizer\Entity\ConversationParticipant\DefaultParticipantNormalizer;
use Wexample\SymfonyConversation\Entity\Conversation;
use Wexample\SymfonyConversation\Entity\ConversationParticipant;
use Wexample\SymfonyConversation\Interface\ConversationPersonPresenterInterface;
use Wexample\SymfonyConversation\Repository\ConversationRepository;
use Wexample\SymfonyConversation\Repository\MessageRepository;
use Wexample\SymfonyConversation\Repository\UserParticipantRepository;
use Wexample\SymfonyConversationDs\Interface\ConversationPinProviderInterface;
use Wexample\SymfonyHelpers\Entity\Interfaces\UserEntityInterface;

/**
 * The conversations of the person signed in as one screen draws them: a tab
 * each — what the application pins first (ConversationPinProviderInterface),
 * then the conversations between people, the one that last spoke first —, and
 * the chat of the one open.
 */
final readonly class ConversationScreenService
{
    /** The component a conversation between people is drawn by. */
    final public const string CHAT_COMPONENT = '@WexampleSymfonyConversationBundle/vue/collection/chat/conversation-chat';

    public function __construct(
        private ConversationRepository $conversations,
        private UserParticipantRepository $userParticipants,
        private MessageRepository $messages,
        private ConversationPersonPresenterInterface $presenter,
        private DefaultParticipantNormalizer $participantNormalizer,
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
     * The tabs, each with what opens it (`key`) and its chat, asked only of
     * the one open.
     *
     * @return list<array{key: string, label: string, avatar?: array, unread: int, chat: callable(): array}>
     */
    public function tabs(UserEntityInterface $user): array
    {
        $tabs = [];

        foreach ($this->pinProviders as $provider) {
            array_push($tabs, ...$provider->getPinnedConversations($user));
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

            $tabs[] = [
                'key' => (string) $conversation->getId(),
                'label' => $this->titleOf($conversation, $others),
                'avatar' => $others ? $this->face($others[0]) : null,
                'unread' => $me ? $this->messages->countUnreadFor($me) : 0,
                'chat' => fn () => [
                    'component' => self::CHAT_COMPONENT,
                    'props' => $this->chatProps($conversation, $user),
                ],
            ];
        }

        return $tabs;
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

    /** @return array{label: string, src: ?string, emoji: ?string} */
    private function face(ConversationParticipant $participant): array
    {
        $person = $this->presenter->present($participant);

        return ['label' => $person['label'], 'src' => $person['src'], 'emoji' => $person['emoji']];
    }
}
