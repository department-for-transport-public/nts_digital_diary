<?php

namespace App\Controller\Admin\Feedback;

use App\Entity\Feedback\Message;
use App\Form\Admin\Feedback\AssignmentType;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route("/feedback/assignment", name: "feedback_assignment_")]
#[IsGranted(attribute: 'ADMIN_FEEDBACK_ASSIGN')]
class AssignmentController extends AbstractController
{
    #[Route("/message/{message}", name: "message")]
    #[Template("admin/feedback/assign.html.twig")]
    public function message(Request $request, WorkflowInterface $feedbackMessageStateMachine, EntityManagerInterface $entityManager, Message $message): array | Response
    {
        $form = $this->createForm(AssignmentType::class, $message);
        $form->handleRequest($request);

        $wasClicked = function(string $buttonName, FormInterface $buttonGroup): bool {
            $button = $buttonGroup->get($buttonName);
            return $button instanceof SubmitButton && $button->isClicked();
        };

        if ($form->isSubmitted()) {
            $buttonGroup = $form->get('button_group');
            $submitWasClicked = $wasClicked('submit', $buttonGroup);
            $rejectWasClicked = $wasClicked('reject', $buttonGroup);

            if ($submitWasClicked && $form->isValid()) {
                if ($form->get('assignedTo')->getData() !== $message->getAssignedTo()) {
                    $feedbackMessageStateMachine->apply(
                        $message,
                        Message::TRANSITION_ASSIGN,
                        [Message::TRANSITION_CONTEXT_ASSIGN_TO => $form->get('assignedTo')->getViewData()]
                    );
                    $request->getSession()->getFlashBag()->add(
                        NotificationBanner::FLASH_BAG_TYPE,
                        $this->getAssignedNotification($message)
                    );
                }
                $entityManager->flush();
                return new RedirectResponse($this->generateUrl('admin_feedback_assignment_dashboard'));
            } else if ($rejectWasClicked) {
                // N.B. form isn't necessarily valid when reject clicked
                $feedbackMessageStateMachine->apply(
                    $message,
                    Message::TRANSITION_REJECT,
                );
                $entityManager->flush();
                return new RedirectResponse($this->generateUrl('admin_feedback_assignment_dashboard'));
            }
        }

        return [
            'form' => $form->createView(),
            'message' => $message,
        ];
    }

    protected function getAssignedNotification(Message $message): NotificationBanner
    {
        return new NotificationBanner(
            new TranslatableMessage('notification.success', [], 'messages'),
            'feedback.assignment.success-notification.heading',
            'feedback.assignment.success-notification.content',
            [NotificationBanner::OPTION_STYLE => NotificationBanner::STYLE_SUCCESS],
            ['assignedTo' => $message->getAssignedTo()],
            'admin'
        );
    }
}