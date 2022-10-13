<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Entity\Journey\Stage;
use App\Utility\ConfirmAction\TravelDiary\DeleteStageConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(name="stage")
 * @Redirect("is_granted('DIARY_KEEPER_WITH_APPROVED_DIARY')", route="traveldiary_dashboard")
 */
class StageController extends AbstractController
{
    /**
     * @Route("/stages/{stageId}/delete", name="_delete")
     * @Entity("stage", expr="repository.findByStageId(stageId)")
     * @Template("travel_diary/stage/delete.html.twig")
     */
    public function delete(Stage $stage, DeleteStageConfirmAction $confirmAction, Request $request)
    {
        return $confirmAction
            ->setSubject($stage)
            ->controller(
                $request,
                $this->generateUrl('traveldiary_journey_view', ['journeyId' => $stage->getJourney()->getId()])
            );
    }
}