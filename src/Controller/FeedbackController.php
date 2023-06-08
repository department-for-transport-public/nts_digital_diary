<?php

namespace App\Controller;

use App\Form\FeedbackType;
use App\Messenger\AlphagovNotify\Email;
use App\Utility\AlphagovNotify\Reference;
use App\Utility\FeedbackEncoder;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class FeedbackController extends AbstractController
{
    public function __construct(protected FeedbackEncoder $feedbackEncoder)
    {}

    /**
     * @Route("/feedback", name="app_feedback")
     */
    public function feedback(MessageBusInterface $messageBus, Request $request, array $feedbackRecipients): Response
    {
        $info = $this->feedbackEncoder->decodeFeedbackFromRequest($request);
        $isLoggedIn = $info['is_logged_in'] ?? false;
        unset($info['is_logged_in']);

        $form = $this->createForm(FeedbackType::class, null, [
            'is_logged_in' => $isLoggedIn,
            'action' => $request->server->get('REQUEST_URI'), // Persist query string
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $submit = $form->get('submit');

            if ($submit instanceof SubmitButton && $submit->isClicked()) {
                $email = $form->has('email') ? $form->get('email')->getData() : null;

                if ($email) {
                    $info['email'] = "Email: $email";
                }

                sort($info);
                $details = join("\n", array_map(fn($line) => "* $line", $info));

                if ($details === '') {
                    $details = 'No details captured';
                }

                $personalisation = [
                    'comments' => $form->get('comments')->getData() ?? '-',
                    'details' => $details,
                ];

                foreach($feedbackRecipients as $feedbackRecipient) {
                    $messageBus->dispatch(new Email(
                        Reference::FEEDBACK_EVENT,
                        null,
                        null,
                        $feedbackRecipient,
                        Reference::FEEDBACK_EMAIL_TEMPLATE_ID,
                        $personalisation,
                        "feedback@" . ((new \DateTime())->format('c')),
                    ));
                }

                return $this->redirectToRoute('app_feedback_thanks');
            }
        }

        return $this->render(
            'feedback/feedback.html.twig',
            [
                'form' => $form->createView(),
            ]);
    }

    /**
     * @Route("/feedback/thanks", name="app_feedback_thanks")
     */
    public function thanks(): Response
    {
        return $this->render('feedback/thanks.html.twig');
    }
}