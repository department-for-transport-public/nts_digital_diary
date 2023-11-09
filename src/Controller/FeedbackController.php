<?php

namespace App\Controller;

use App\Entity\Feedback\CategoryEnum;
use App\Form\FeedbackType;
use App\Utility\Feedback\MessageEncoder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/{category}", name: "app_feedback_", requirements: ['category' => "feedback|support"])]
class FeedbackController extends AbstractController
{
    protected const THANKS_PAGE_REDIRECT_SESSION_KEY = "feedback-thanks-redirect";

    public function __construct(protected MessageEncoder $feedbackEncoder) {}

    #[Route(name: 'form')]
    public function feedback(Request $request, EntityManagerInterface $entityManager, string $category): Response
    {
        $message = $this->feedbackEncoder->decodeFeedbackFromRequest($request)
            ->setCategory(CategoryEnum::from($category));
        $isLoggedIn = $message->getCurrentUserSerial();

        $form = $this->createForm(FeedbackType::class, $message, [
            'is_logged_in' => $isLoggedIn,
            'action' => $request->server->get('REQUEST_URI'), // Persist query string
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($message);
            $entityManager->flush();

            $request->getSession()->set(self::THANKS_PAGE_REDIRECT_SESSION_KEY, $message->getPage());
            return $this->redirectToRoute('app_feedback_thanks', ['category' => $category]);
        }

        return $this->render(
            'feedback/feedback.html.twig',
            [
                'form' => $form->createView(),
            ]);
    }

    #[Route("/thanks", name: "thanks")]
    public function thanks(Request $request, string $category): Response
    {
        return $this->render('feedback/thanks.html.twig', [
            'category' => $category,
            'sourcePage' => $request->getSession()->remove(self::THANKS_PAGE_REDIRECT_SESSION_KEY),
        ]);
    }
}