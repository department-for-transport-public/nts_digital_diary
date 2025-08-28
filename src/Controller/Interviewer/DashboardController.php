<?php

namespace App\Controller\Interviewer;

use App\Controller\Frontend\FrontendController;
use App\Entity\AreaPeriod;
use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Exception\RedirectResponseException;
use App\Repository\AreaPeriodRepository;
use App\Security\Voter\Interviewer\AreaPeriodVoter;
use App\Security\Voter\Interviewer\HouseholdVoter;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(name: "dashboard")]
class DashboardController extends AbstractController
{
    #[Route("", name:"")]
    #[Template("interviewer/dashboard/areas.html.twig")]
    public function areas(Request $request): array
    {
        $this->redirectToGuideIfAppropriate($request);

        return $this->getAreasListData(
            fn(AreaPeriod $period): bool => !$period->isArchived(),
            groupAreas: false,
            includeOverdueHouseholds: true,
        );
    }

    #[Route("/areas-archived", name: "_archived_areas")]
    #[Template("interviewer/dashboard/areas_archived.html.twig")]
    public function archived(): array
    {
        return $this->getAreasListData(
            fn(AreaPeriod $period): bool => $period->isArchived(),
            groupAreas: true,
            includeOverdueHouseholds: false,
        );
    }

    protected function getExcludeFutureAreasFilterCondition(): \Closure
    {
        return fn(AreaPeriod $ap):bool =>
            $ap->getFirstValidDiaryStartDate() < ((new \DateTime())->add(new \DateInterval("P3W")));
    }

    protected function getAreasListData(\Closure $filterCondition, bool $groupAreas, bool $includeOverdueHouseholds): array
    {
        $interviewer = $this->getInterviewer();
        $areaPeriods = $interviewer->getAreaPeriods()
            // don't show APs far in to the future
            ->filter($this->getExcludeFutureAreasFilterCondition())
            ->filter($filterCondition);

        $data = [
            'interviewer' => $interviewer,
        ];

        if ($includeOverdueHouseholds) {
            $data = $this->addOverdueHouseholdsData($data, $areaPeriods->toArray(), $interviewer);
        }

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

        return $this->addOverdueHouseholdsData([
            'interviewer' => $interviewer,
            'areaPeriod' => $areaPeriod,
        ], [$areaPeriod], $interviewer);
    }

    #[Route("/household/{household}", name: "_household")]
    #[Template("interviewer/dashboard/household.html.twig")]
    #[IsGranted(HouseholdVoter::VIEW, "household", statusCode: 404)]
    public function household(
        #[MapEntity(expr: "repository.findWithRelations(household)")]
        Household $household,
    ): array
    {
        $interviewer = $this->getInterviewer();

        return [
            'interviewer' => $interviewer,
            'household' => $household,
        ];
    }

    #[Template("interviewer/dashboard/diary_keeper.html.twig")]
    #[Route("/diary-keeper/{diaryKeeper}", name: "_diary_keeper")]
    #[IsGranted(
        attribute: new Expression("is_granted('VIEW_HOUSEHOLD', object)"),
        subject: new Expression('args["diaryKeeper"].getHousehold()'),
        statusCode: 404
    )]
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

    protected function addOverdueHouseholdsData(array $data, array $areaPeriods, Interviewer $interviewer): array
    {
        $overdueHouseholdsForInterviewer = $this->entityManager->getRepository(Household::class)->findOverdueHouseholdsForInterviewer($areaPeriods, $interviewer);
        $householdsWithOverdueAreas = array_unique(array_map(fn(Household $h): ?string => $h->getAreaPeriod()->getArea(), $overdueHouseholdsForInterviewer));

        return array_merge($data, [
            'overdueHouseholds' => $overdueHouseholdsForInterviewer,
            'areasWithOverdueHouseholds' => $householdsWithOverdueAreas,
        ]);
    }
}
