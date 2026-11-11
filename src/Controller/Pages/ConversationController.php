<?php

namespace Wexample\SymfonyConversationDs\Controller\Pages;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyConversation\Service\ConversationService;
use Wexample\SymfonyConversationDs\Service\ConversationScreenService;
use Wexample\SymfonyConversationDs\Traits\SymfonyConversationDsBundleClassTrait;
use Wexample\SymfonyHelpers\Entity\Interfaces\UserEntityInterface;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;

/**
 * The conversations of the person signed in, one screen made to be held in
 * the dock's panel: a tab each in a column of faces — the agent's first —,
 * the open one's chat beside them, a tab to start one with someone at their
 * foot. A tab opens the screen again in the same place (`self`), on it.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route(path: 'conversations/', name: 'conversation_')]
final class ConversationController extends AbstractPagesController
{
    use SymfonyConversationDsBundleClassTrait;

    public const string ROUTE_INDEX = 'conversation_index';

    public const string ROUTE_WITH = 'conversation_with';

    /** The tab open: a conversation's key; `new` for the one starting. */
    public const string QUERY_OPEN = 'open';

    public const string OPEN_NEW = 'new';

    #[Route(path: '', name: 'index')]
    public function index(Request $request, ConversationScreenService $screen): Response
    {
        $tabs = $screen->tabs($this->getSignedIn());
        $open = (string) $request->query->get(self::QUERY_OPEN, '');

        if ('' === $open && $tabs) {
            $open = $tabs[0]['key'];
        }

        $chat = null;
        $items = [];

        foreach ($tabs as $tab) {
            $active = $tab['key'] === $open;
            $chat = $active ? ($tab['chat'])() : $chat;
            $items[] = [
                'href' => $this->generateUrl(self::ROUTE_INDEX, [self::QUERY_OPEN => $tab['key']]),
                'label' => $tab['label'],
                'avatar' => $tab['avatar'] ?? null,
                'unread' => $tab['unread'] ?? 0,
                'active' => $active,
            ];
        }

        return $this->renderPage('index', [
            'conversation_tabs' => $items,
            'conversation_add' => [
                'href' => $this->generateUrl(self::ROUTE_INDEX, [self::QUERY_OPEN => self::OPEN_NEW]),
                'active' => self::OPEN_NEW === $open,
            ],
            'conversation_chat' => $chat,
            'conversation_starting' => self::OPEN_NEW === $open,
        ]);
    }

    /** The conversation with that person, opened if none is held yet, on its tab. */
    #[Route(path: 'with/{user}', name: 'with', requirements: ['user' => Requirement::UUID])]
    public function with(
        string $user,
        EntityManagerInterface $entityManager,
        ConversationService $conversationService,
    ): Response {
        $other = $entityManager->getRepository(UserEntityInterface::class)->find($user)
            ?? throw $this->createNotFoundException();

        $conversation = $conversationService->openWith($this->getSignedIn(), $other);

        return $this->redirectToRoute(self::ROUTE_INDEX, [self::QUERY_OPEN => (string) $conversation->getId()]);
    }

    private function getSignedIn(): UserEntityInterface
    {
        $user = $this->getUser();

        if (! $user instanceof UserEntityInterface) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
