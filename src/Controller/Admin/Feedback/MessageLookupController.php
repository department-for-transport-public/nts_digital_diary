<?php

namespace App\Controller\Admin\Feedback;

use App\Form\Admin\Feedback\MessageLookupType;
use App\Repository\Feedback\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted(attribute: 'ADMIN_FEEDBACK_VIEW')]
class MessageLookupController extends AbstractController
{
    #[Route(path: '/assignment/message-lookup', name: 'feedback_message_lookup')]
    public function lookup(Request $request, MessageRepository $messageRepository): Response
    {
        $form = $this->createForm(MessageLookupType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messageIdField = $form->get('messageId');
            $messageId = $messageIdField->getData();
            $message = $messageRepository->findOneBy(['id' => $messageId]);

            if ($message && $this->isGranted('ADMIN_FEEDBACK_VIEW_MESSAGE', $message)) {
                return $this->redirectToRoute('admin_feedback_message_view', ['message' => $message->getId()]);
            } else {
                $messageIdField->addError(new FormError('No such message ID found'));
            }
        }

        return $this->render('admin/feedback/message_lookup.html.twig', [
            'form' => $form,
        ]);
    }
}
