<?php

namespace App\Controller\TravelDiary;

use App\Attribute\Redirect;
use App\Entity\Journey\Journey;
use App\Repository\Journey\JourneyRepository;
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use App\Utility\ConfirmAction\TravelDiary\DeleteJourneyConfirmAction;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Redirect("!is_granted('EDIT_DIARY')", route: "traveldiary_dashboard")]
#[Route(path: '/journey/{journeyId}', name: 'journey', requirements: ['journeyId' => '\w{26}'])]
#[IsGranted(DiaryAccessVoter::ACCESS, subject: 'journey', statusCode: 404)]
class JourneyController extends AbstractController
{
    #[Route(name: '_view')]
    #[Template('travel_diary/journey/view.html.twig')]
    public function view(
        #[MapEntity(expr: "repository.findByJourneyId(journeyId)")]
        Journey           $journey,
        JourneyRepository $journeyRepository
    ): array
    {
        $diaryKeeper = $journey->getDiaryDay()->getDiaryKeeper();

        return [
            'diaryKeeper' => $diaryKeeper,
            'day' => $journey->getDiaryDay(),
            'journey' => $journey,
            'sharedFromName' => $journeyRepository->getSharedFromName($journey),
            'sharedToNames' => $journeyRepository->getSharedToNames($journey),
        ];
    }

    #[Route(path: '/delete', name: '_delete')]
    #[Template('travel_diary/journey/delete.html.twig')]
    public function delete(
        #[MapEntity(expr: "repository.findByJourneyId(journeyId)")]
        Journey                    $journey,
        DeleteJourneyConfirmAction $confirmAction,
        Request                    $request
    ): RedirectResponse|array
    {
        return $confirmAction
            ->setSubject($journey)
            ->controller(
                $request,
                $this->generateUrl('traveldiary_dashboard_day', ['dayNumber' => $journey->getDiaryDay()->getNumber()]),
                $this->generateUrl('traveldiary_journey_view', ['journeyId' => $journey->getId()])
            );
    }
}
