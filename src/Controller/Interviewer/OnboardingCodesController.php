<?php

namespace App\Controller\Interviewer;

use App\Entity\AreaPeriod;
use App\Entity\OtpUser;
use App\Features;
use App\Repository\OtpUserRepository;
use App\Security\OneTimePassword\PasscodeGenerator;
use App\Security\Voter\Interviewer\AreaPeriodVoter;
use App\Utility\TravelDiary\AreaPeriodHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OnboardingCodesController extends AbstractController
{
    public function __construct(
        EntityManagerInterface               $entityManager,
        Security                             $security,
        protected readonly OtpUserRepository $otpUserRepository,
        protected readonly PasscodeGenerator $passcodeGenerator,
        protected readonly AreaPeriodHelper  $areaPeriodHelper,
    ) {
        parent::__construct($entityManager, $security);
    }

    #[Route("/areas/{areaPeriod}/onboarding-codes", name: "area_onboarding_codes")]
    #[Template("interviewer/onboarding_codes/onboarding_codes.html.twig")]
    #[IsGranted(AreaPeriodVoter::IS_SUBSCRIBED, subject: "areaPeriod", statusCode: 404)]
    public function onboardingCodes(AreaPeriod $areaPeriod): array
    {
        if (!Features::isEnabled(Features::SHOW_ONBOARDING_CODES)) {
            throw new NotFoundHttpException();
        }

        $interviewer = $this->getInterviewer();

        return [
            'interviewer' => $interviewer,
            'areaPeriod' => $areaPeriod,
            'codes' => $this->getCodesForArea($areaPeriod),
        ];
    }

    protected function getCodesForArea(AreaPeriod $areaPeriod): array
    {
        $codes = $this->otpUserRepository->findForInterviewerDashboard($areaPeriod);

        if (count($codes) === 0) {
            $codes = $this->areaPeriodHelper->createCodesForArea($areaPeriod);
        }

        $codes = array_map(fn(OtpUser $o): array => [
            'id' => $o->getUserIdentifier(),
            'code' => $this->passcodeGenerator->getPasswordForUserIdentifier($o->getUserIdentifier()),
            'household' => $o->getHousehold(),
        ], $codes);

        usort($codes, fn($a, $b): int => $a['id'] <=> $b['id']);

        return $codes;
    }
}