<?php

namespace App\Controller\OnBoarding;

use App\Controller\AbstractController;
use App\Entity\OtpUser;
use App\Event\CompleteOnboardingEvent;
use App\Form\OnBoarding\ConfirmHouseholdType;
use Psr\EventDispatcher\EventDispatcherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SubmitController extends AbstractController
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher) {}

    /**
     * @Route("/submit", name="submit")
     * @IsGranted("ONBOARDING_EDIT")
     */
    public function showSummary(Request $request): Response
    {
        /** @var OtpUser $user */
        $user = $this->getUser();
        $household = $user->getHousehold();

        $form = $this->createForm(ConfirmHouseholdType::class, $household, [
            'confirm_button_options' => [
                'label' => 'submit.confirm-and-submit',
            ],
            'cancel_link_options' => [
                'href' => $this->generateUrl('onboarding_dashboard'),
            ],
            'translation_domain' => 'on-boarding',
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->eventDispatcher->dispatch(new CompleteOnboardingEvent($household));
            return $this->redirectToRoute('onboarding_dashboard');
        }

        return $this->render('on_boarding/submit/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}