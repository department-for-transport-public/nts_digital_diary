<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Entity\Journey\Stage;
use App\Security\Voter\TravelDiary\DiaryAccessVoter;
use App\Utility\ConfirmAction\TravelDiary\DeleteStageConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/stages/{stageId}", name="stage")
 * @Entity("stage", expr="repository.findByStageId(stageId)")
 * @IsGranted(DiaryAccessVoter::ACCESS, subject="stage", statusCode=404)
 * @Redirect("!is_granted('EDIT_DIARY')", route="traveldiary_dashboard")
 */
class StageController extends AbstractController
{
    /**
     * @Route("/delete", name="_delete")
     * @Template("travel_diary/stage/delete.html.twig")
     */
    public function delete(Stage $stage, DeleteStageConfirmAction $confirmAction, Request $request): array | RedirectResponse
    {
        return $confirmAction
            ->setSubject($stage)
            ->controller(
                $request,
                $this->generateUrl('traveldiary_journey_view', ['journeyId' => $stage->getJourney()->getId()])
            );
    }
}