<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Security\Voter\DiaryKeeperVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route(name="dashboard")
 */
class DashboardController extends AbstractController
{
    /**
     * @Route("", name="")
     */
    public function dashboard(UserInterface $user): Response
    {
        $diaryKeeper = $this->getDiaryKeeper($user);

        $template = $this->isGranted(DiaryKeeperVoter::DIARY_KEEPER_WITH_APPROVED_DIARY) ?
            'travel_diary/dashboard/approved.html.twig' :
            'travel_diary/dashboard/dashboard.html.twig';

        return $this->render($template, ['diaryKeeper' => $diaryKeeper]);
    }

    /**
     * @Route("/day-{dayNumber}", name="_day", requirements={"dayNumber": "[0-7]{1}"})
     * @Template("travel_diary/dashboard/day.html.twig")
     * @Redirect("is_granted('DIARY_KEEPER_WITH_APPROVED_DIARY')", route="traveldiary_dashboard")
     */
    public function day(UserInterface $user, int $dayNumber): array
    {
        $diaryKeeper = $this->getDiaryKeeper($user, true);
        $day = $diaryKeeper->getDiaryDayByNumber($dayNumber);

        return [
            'diaryKeeper' => $diaryKeeper,
            'day' => $day,
        ];
    }
}