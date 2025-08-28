<?php

namespace App\Controller\TravelDiary;

use App\Attribute\Redirect;
use App\Entity\Journey\Stage;
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use App\Utility\ConfirmAction\TravelDiary\DeleteStageConfirmAction;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Redirect("!is_granted('EDIT_DIARY')", route: "traveldiary_dashboard")]
#[Route(path: '/stages/{stageId}', name: 'stage')]
#[IsGranted(DiaryAccessVoter::ACCESS, subject: 'stage', statusCode: 404)]
class StageController extends AbstractController
{
    #[Route(path: '/delete', name: '_delete')]
    #[Template('travel_diary/stage/delete.html.twig')]
    public function delete(
        #[MapEntity(expr: "repository.findByStageId(stageId)")]
        Stage                    $stage,
        DeleteStageConfirmAction $confirmAction,
        Request                  $request
    ): array|RedirectResponse
    {
        return $confirmAction
            ->setSubject($stage)
            ->controller(
                $request,
                $this->generateUrl('traveldiary_journey_view', ['journeyId' => $stage->getJourney()->getId()])
            );
    }
}
