<?php

namespace App\Controller\Interviewer;

use App\Entity\AreaPeriod;
use App\Entity\OtpUser;
use App\Repository\OtpUserRepository;
use App\Security\OneTimePassword\PasscodeGenerator;
use App\Utility\AreaPeriodHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class OnboardingCodesController extends AbstractController
{
    protected OtpUserRepository $otpUserRepository;
    protected PasscodeGenerator $passcodeGenerator;
    private AreaPeriodHelper $areaPeriodHelper;

    public function __construct(EntityManagerInterface $entityManager, OtpUserRepository $otpUserRepository, PasscodeGenerator $passcodeGenerator, AreaPeriodHelper $areaPeriodHelper)
    {
        parent::__construct($entityManager);
        $this->otpUserRepository = $otpUserRepository;
        $this->passcodeGenerator = $passcodeGenerator;
        $this->areaPeriodHelper = $areaPeriodHelper;
    }

    /**
     * @Route("/areas/{areaPeriod}/onboarding-codes", name="area_onboarding_codes")
     * @Template()
     */
    public function onboardingCodes(UserInterface $user, AreaPeriod $areaPeriod): array
    {
        $interviewer = $this->getInterviewer($user);
        $this->checkInterviewerIsSubscribedToAreaPeriod($areaPeriod, $interviewer);

        return [
            'interviewer' => $interviewer,
            'areaPeriod' => $areaPeriod,
            'codes' => $this->getCodesForArea($areaPeriod),
        ];
    }

    protected function getCodesForArea(AreaPeriod $areaPeriod): array
    {
        $codes = $this->otpUserRepository->findBy(['areaPeriod' => $areaPeriod, 'household' => null]);

        if (count($codes) === 0) {
            $codes = $this->areaPeriodHelper->createCodesForArea($areaPeriod);
        }

        $codes = array_map(fn(OtpUser $o) => [
            'id' => $o->getUserIdentifier(),
            'code' => $this->passcodeGenerator->getPasswordForUserIdentifier($o->getUserIdentifier())
        ], $codes);

        usort($codes, fn($a, $b) => $a['id'] <=> $b['id']);

        return $codes;
    }
}