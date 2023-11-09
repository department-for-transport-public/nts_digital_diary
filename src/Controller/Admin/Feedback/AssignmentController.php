<?php

namespace App\Controller\Admin\Feedback;

use App\Entity\Feedback\Message;
use App\Form\Admin\Feedback\AssignmentType;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Model\NotificationBanner;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route("/feedback/assignment", name: "feedback_assignment_")]
#[Security("is_granted('ROLE_FEEDBACK_ASSIGNER')")]
class AssignmentController extends AbstractController
{
    #[Route("/message/{message}", name: "message")]
    #[Template("admin/feedback/assign.html.twig")]
    public function message(Request $request, WorkflowInterface $feedbackMessageStateMachine, EntityManagerInterface $entityManager, Message $message): array | Response
    {
        $form = $this->createForm(AssignmentType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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