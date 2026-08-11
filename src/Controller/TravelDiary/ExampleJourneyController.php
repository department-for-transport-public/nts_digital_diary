<?php

namespace App\Controller\TravelDiary;

use App\Attribute\Redirect;
use App\Entity\Journey\Journey;
use App\Utility\TravelDiary\ExampleJourneyFixtures;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Redirect("!is_granted('EDIT_DIARY')", route: "traveldiary_dashboard")]
class ExampleJourneyController extends AbstractController
{
    #[Route(path: '/journey/{journeyId}', name: 'journey_view_example', requirements: ['journeyId' => 'example\-\d'])]
    #[Template('travel_diary/journey/view.html.twig')]
    public function viewExample(UserInterface $user, ExampleJourneyFixtures $journeyFixtures, string $journeyId): array
    {
        $diaryKeeper = $this->getDiaryKeeper($user, true);
        $day = $journeyFixtures->getExampleDay($diaryKeeper);
        $journey = $day->getJourneys()->findFirst(fn($x, Journey $j) => $j->getId() === $journeyId);

        return [
            'diaryKeeper' => $diaryKeeper,
            'day' => $day,
            'journey' => $journey,
            'sharedFromName' => null,
            'sharedToNames' => null,
        ];

    }
}
