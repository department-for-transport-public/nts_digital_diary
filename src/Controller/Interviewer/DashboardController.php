<?php

namespace App\Controller\Interviewer;

use App\Controller\FrontendController;
use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Exception\RedirectResponseException;
use App\Repository\AreaPeriodRepository;
use App\Security\Voter\Interviewer\AreaPeriodVoter;
use App\Security\Voter\Interviewer\HouseholdVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route(name: "dashboard")]
class DashboardController extends AbstractController
{
    #[Route("", name:"")]
    #[Template("interviewer/dashboard/areas.html.twig")]
    public function areas(Request $request): array
    {
        $this->redirectToGuideIfAppropriate($request);

        return $this->getAreasListData(
            fn(AreaPeriod $period) => !$period->isArchived(),
            false
        );
    }

    #[Route("/areas-archived", name: "_archived_areas")]
    #[Template("interviewer/dashboard/areas_archived.html.twig")]
    public function archived(): array
    {
        return $this->getAreasListData(
            fn(AreaPeriod $period) => $period->isArchived(),
            true
        );
    }

    protected function getAreasListData(\Closure $filterCondition, bool $groupAreas): array
    {
        $interviewer = $this->getInterviewer();
        $data = ['interviewer' => $interviewer];

        $areaPeriods = $interviewer->getAreaPeriods()->filter($filterCondition);

        if ($groupAreas) {
            $data['areaPeriodsByYear'] = AreaPeriodRepository::groupAreaPeriodsByYear($areaPeriods);
        } else {
            $data['areaPeriods'] = $areaPeriods;
        }

        return $data;
    }

    #[Route("/areas/{areaPeriod}", name: "_area")]
    #[Template("interviewer/dashboard/area.html.twig")]
    #[IsGranted(AreaPeriodVoter::IS_SUBSCRIBED, "areaPeriod", statusCode: 404)]
    public function area(AreaPeriod $areaPeriod): array
    {
        $interviewer = $this->getInterviewer();

        return [
            'interviewer' => $interviewer,
            'areaPeriod' => $areaPeriod,
        ];
    }

    #[Route("/household/{household}", name: "_household")]
    #[Template("interviewer/dashboard/household.html.twig")]
    #[IsGranted(HouseholdVoter::VIEW, "household", statusCode: 404)]
    public function household(Household $household): array
    {
        $interviewer = $this->getInterviewer();

        return [
            'interviewer' => $interviewer,
            'household' => $household,
        ];
    }

    #[Route("/diary-keeper/{diaryKeeper}", name: "_diary_keeper")]
    #[Template("interviewer/dashboard/diary_keeper.html.twig")]
    #[Security("is_granted('VIEW_HOUSEHOLD', diaryKeeper.getHousehold())", statusCode: 404)]
    public function diaryKeeper(DiaryKeeper $diaryKeeper): array
    {
        $interviewer = $this->getInterviewer();

        return [
            'interviewer' => $interviewer,
            'diaryKeeper' => $diaryKeeper,
        ];
    }

    public function redirectToGuideIfAppropriate(Request $request): void
    {
        // See constant for commentary
        $session = $request->getSession();
        $shouldRedirectToGuideIfInterviewer = $session->get(FrontendController::REDIRECT_INTERVIEWER_DASHBOARD_TO_GUIDE, null);
        $session->remove(FrontendController::REDIRECT_INTERVIEWER_DASHBOARD_TO_GUIDE);
        if ($shouldRedirectToGuideIfInterviewer !== null) {
            throw new RedirectResponseException(new RedirectResponse($shouldRedirectToGuideIfInterviewer));
        }
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