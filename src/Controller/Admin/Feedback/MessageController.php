<?php

namespace App\Controller\Admin\Feedback;

use App\Entity\Feedback\Message;
use App\Form\Admin\Feedback\NoteType;
use App\Utility\ConfirmAction\Admin\FeedbackMessageStateTransitionConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\ExpressionLanguage\Expression;

#[Route("/feedback/message/{message}", name: "feedback_message_")]
#[IsGranted(attribute: 'ADMIN_FEEDBACK_VIEW_MESSAGE', subject: 'message')]
class MessageController extends AbstractController
{
    #[Route(name: "view")]
    #[Template("admin/feedback/view_message.html.twig")]
    public function view(Request $request, EntityManagerInterface $entityManager, Message $message): array | Response
    {
        $form = $this->createForm(NoteType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->addNote($note = $form->getData());
            $entityManager->persist($note);
            $entityManager->flush();
            return new RedirectResponse($request->getUri() . '#notes');
        }

        return [
            'form' => $form->createView(),
            'message' => $message,
        ];
    }

    #[Route("/transition/{transition}", name: "transition")]
    #[Template("admin/feedback/transition_message.html.twig")]
    #[IsGranted(
        attribute: new Expression("workflow_can(subject['message'], subject['transition'])"),
        subject: ['transition', 'message']
    )]
    public function transition(FeedbackMessageStateTransitionConfirmAction $stateTransitionConfirmAction, Request $request, Message $message, string $transition): array | Response
    {
        return $stateTransitionConfirmAction
            ->setSubject($message)
            ->setTransition($transition)
            ->controller(
                $request,
                $this->generateUrl('admin_feedback_message_view', ["message" => $message->getId() ])
            );
    }
}