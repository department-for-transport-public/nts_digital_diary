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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class OnboardingCodesController extends AbstractController
{
    protected OtpUserRepository $otpUserRepository;
    protected PasscodeGenerator $passcodeGenerator;
    private AreaPeriodHelper $areaPeriodHelper;

    public function __construct(EntityManagerInterface $entityManager, Security $security, OtpUserRepository $otpUserRepository, PasscodeGenerator $passcodeGenerator, AreaPeriodHelper $areaPeriodHelper)
    {
        parent::__construct($entityManager, $security);
        $this->otpUserRepository = $otpUserRepository;
        $this->passcodeGenerator = $passcodeGenerator;
        $this->areaPeriodHelper = $areaPeriodHelper;
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

        $codes = array_map(fn(OtpUser $o) => [
            'id' => $o->getUserIdentifier(),
            'code' => $this->passcodeGenerator->getPasswordForUserIdentifier($o->getUserIdentifier()),
            'household' => $o->getHousehold(),
        ], $codes);

        usort($codes, fn($a, $b) => $a['id'] <=> $b['id']);

        return $codes;
    }
}