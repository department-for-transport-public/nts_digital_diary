<?php

namespace App\Controller\OnBoarding;

use App\Controller\AbstractController;
use App\Entity\Household;
use App\Entity\OtpUser;
use App\Features;
use App\Form\OnBoarding\ConfirmHouseholdType;
use App\Utility\AccountCreationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SubmitController extends AbstractController
{
    private AccountCreationHelper $accountCreationHelper;
    private EntityManagerInterface $entityManager;

    public function __construct(AccountCreationHelper $accountCreationHelper, EntityManagerInterface $entityManager)
    {
        $this->accountCreationHelper = $accountCreationHelper;
        $this->entityManager = $entityManager;
    }

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
            return $this->submitOnboarding($household);
        }

        return $this->render('on_boarding/submit/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    protected function submitOnboarding(Household $household): RedirectResponse
    {
        // disable/delete the onboarding code
        $household->setIsOnboardingComplete(true);
        $this->entityManager->flush();

        foreach ($household->getDiaryKeepers() as $diaryKeeper) {
            if ($diaryKeeper->hasIdentifierForLogin()) {
                $this->accountCreationHelper->sendAccountCreationEmail($diaryKeeper);
            }
        }

        return $this->redirectToRoute('onboarding_dashboard');
    }
}