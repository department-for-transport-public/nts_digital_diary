<?php

namespace App\Controller\Admin\Tools;

use App\Controller\AbstractController;
use App\Entity\AreaPeriod;
use App\Entity\OtpUser;
use App\Form\Admin\Tools\DirectOnboardingType;
use App\Form\Admin\Tools\CheckLetterType;
use App\Repository\HouseholdRepository;
use App\Security\OneTimePassword\DirectOnboardingTrainingUserProvider;
use App\Utility\DirectOnboardingTestUrlGenerator;
use App\Utility\TravelDiary\SerialHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tools', name: 'tools_')]
class IndexController extends AbstractController
{
    public function __construct(
        protected DirectOnboardingTestUrlGenerator $directOnboardingTestUrlGenerator,
        protected HouseholdRepository $householdRepository,
    ) {}

    #[Route(name: 'index')]
    public function index(DirectOnboardingTrainingUserProvider $userProvider): Response
    {
        $serialForm = $this->createForm(CheckLetterType::class, null, [
            'action' => $this->generateUrl('admin_tools_check_letter_generator'),
        ]);
        $directOnboardingForm = $this->createForm(DirectOnboardingType::class, null, [
            'action' => $this->generateUrl('admin_tools_direct_onboarding_url_generator'),
        ]);
        return $this->render('admin/tools/index.html.twig', [
            'serialForm' => $serialForm,
            'directOnboardingForm' => $directOnboardingForm,
            'directOnboardingTrainingUrl' => $this->directOnboardingTestUrlGenerator->generateUrl(
                $userProvider->loadUserByIdentifier(DirectOnboardingTrainingUserProvider::USER_IDENTIFIER),
                1
            ),
        ]);
    }

    #[Route('/check-letter-generator', name: 'check_letter_generator')]
    public function checkLetter(Request $request): Response
    {
        $form = $this->createForm(CheckLetterType::class);
        $form->handleRequest($request);

        $serial = $form->get('serial')->getData();
        return $this->render('admin/tools/check_letter.html.twig', [
            'serial' => $serial,
            'checkLetter' => SerialHelper::getCheckLetter(...array_values($serial)),
        ]);
    }

    #[Route('/direct-onboarding-url-generator', name: 'direct_onboarding_url_generator')]
    public function directOnboardingUrls(Request $request): Response
    {
        $form = $this->createForm(DirectOnboardingType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            return $this->redirectToRoute('admin_tools_index');
        }

        /** @var AreaPeriod $area */
        $area = $form->get('area')->getData();
        $addressNumber = 1;

        return $this->render('admin/tools/direct-onboarding-urls.html.twig', [
            'area' => $area,
            'directOnboardingUrls' => $area->getOtpUsers()->map(
                function(OtpUser $u) use (&$addressNumber): array {
                    return [
                        'in_progress' => $u->getHousehold() instanceof \App\Entity\Household,
                        'passcode1' => $u->getUserIdentifier(),
                        'complete' => $u->getHousehold()?->getIsOnboardingComplete(),
                        'serialExists' => (bool) $this->householdRepository->findBy(['areaPeriod' => $u->getAreaPeriod(), 'addressNumber' => $addressNumber, 'householdNumber' => 1]),
                        'url' => $this->directOnboardingTestUrlGenerator->generateUrl($u, $addressNumber++),
                    ];
                }
            ),
        ]);
    }
}
