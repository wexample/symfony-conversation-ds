<?php

namespace Wexample\SymfonyConversationDs\Controller\Pages;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyConversation\Entity\UserConversationParticipant;
use Wexample\SymfonyConversation\Repository\ConversationRepository;
use Wexample\SymfonyConversation\Service\ConversationService;
use Wexample\SymfonyConversationDs\Service\ConversationScreenService;
use Wexample\SymfonyConversationDs\Traits\SymfonyConversationDsBundleClassTrait;
use Wexample\SymfonyHelpers\Entity\Interfaces\UserEntityInterface;
use Wexample\SymfonyHelpers\Voter\AbstractEntityVoter;
use Wexample\SymfonyLoader\Controller\AbstractPagesController;

/**
 * The conversations of the person signed in: the list of them, one read and
 * written in beside it, one opened with someone — from a button on their
 * profile, say.
 */
#[IsGranted('IS_AUTHENTICATED')]
#[Route(path: 'conversations/', name: 'conversation_')]
final class ConversationController extends AbstractPagesController
{
    use SymfonyConversationDsBundleClassTrait;

    public const string ROUTE_INDEX = 'conversation_index';

    public const string ROUTE_SHOW = 'conversation_show';

    public const string ROUTE_WITH = 'conversation_with';

    #[Route(path: '', name: 'index')]
    public function index(ConversationScreenService $screen): Response
    {
        return $this->renderPage('index', [
            'conversation_items' => $screen->listItems($this->getSignedIn()),
        ]);
    }

    #[Route(path: '{id}', name: 'show', requirements: ['id' => Requirement::UUID])]
    public function show(
        string $id,
        ConversationRepository $conversations,
        ConversationScreenService $screen,
    ): Response {
        $conversation = $conversations->find($id);

        if (! $conversation || ! ConversationScreenService::isBetweenPeople($conversation)) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted(AbstractEntityVoter::VIEW, $conversation);
        $user = $this->getSignedIn();
        $others = array_values(array_filter(
            $conversation->getParticipants()->toArray(),
            fn ($participant) => ! $participant instanceof UserConversationParticipant || ! $participant->getUser()->getId()->equals($user->getId())
        ));

        return $this->renderPage('show', [
            'conversation_items' => $screen->listItems($user, $conversation),
            'page_title' => $screen->titleOf($conversation, $others),
            'conversation_chat' => $screen->chatProps($conversation, $user),
        ]);
    }

    /** The conversation with that person, opened if none is held yet. */
    #[Route(path: 'with/{user}', name: 'with', requirements: ['user' => Requirement::UUID])]
    public function with(
        string $user,
        EntityManagerInterface $entityManager,
        ConversationService $conversationService,
    ): Response {
        $other = $entityManager->getRepository(UserEntityInterface::class)->find($user)
            ?? throw $this->createNotFoundException();

        $conversation = $conversationService->openWith($this->getSignedIn(), $other);

        return $this->redirectToRoute(self::ROUTE_SHOW, ['id' => $conversation->getId()]);
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
