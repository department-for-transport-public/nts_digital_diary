<?php

namespace App\Controller\Interviewer;

use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Repository\AreaPeriodRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route(name="dashboard")
 */
class DashboardController extends AbstractController
{
    /**
     * @Route("", name="")
     * @Template()
     */
    public function areas(UserInterface $user): array
    {
        $interviewer = $this->getInterviewer($user);

        return [
            'interviewer' => $interviewer,
            'areaPeriodsByYear' => AreaPeriodRepository::groupAreaPeriodsByYear($interviewer->getAreaPeriods()),
        ];
    }

    /**
     * @Route("/areas/{areaPeriod}", name="_area")
     * @Template()
     */
    public function area(UserInterface $user, AreaPeriod $areaPeriod): array
    {
        $interviewer = $this->getInterviewer($user);
        $this->checkInterviewerIsSubscribedToAreaPeriod($areaPeriod, $interviewer);

        return [
            'interviewer' => $interviewer,
            'areaPeriod' => $areaPeriod,
        ];
    }

    /**
     * @Route("/household/{household}", name="_household")
     * @Template()
     * @Security("household.getIsOnboardingComplete()")
     */
    public function household(UserInterface $user, Household $household): array
    {
        $interviewer = $this->getInterviewer($user);
        $this->checkInterviewerIsSubscribedToAreaPeriod($household->getAreaPeriod(), $interviewer);

        return [
            'interviewer' => $interviewer,
            'household' => $household,
        ];
    }

    /**
     * @Route("/diary-keeper/{diaryKeeper}", name="_diary_keeper")
     * @Template()
     * @Security("diaryKeeper.getHousehold().getIsOnboardingComplete()")
     */
    public function diaryKeeper(UserInterface $user, DiaryKeeper $diaryKeeper): array
    {
        $interviewer = $this->getInterviewer($user);
        $this->checkInterviewerIsSubscribedToAreaPeriod($diaryKeeper->getHousehold()->getAreaPeriod(), $interviewer);

        return [
            'interviewer' => $interviewer,
            'diaryKeeper' => $diaryKeeper,
        ];
    }

    protected function getSubscriptionViewData(AreaPeriod $areaPeriod, FormInterface $form, Interviewer $interviewer, string $action): array
    {
        return [
            'areaPeriod' => $areaPeriod,
            'areaNumber' => $areaPeriod->getArea(),
            'action' => $action,
            'form' => $form->createView(),
            'interviewer' => $interviewer,
            'translationDomain' => self::TRANSLATION_DOMAIN,
        ];
    }
}