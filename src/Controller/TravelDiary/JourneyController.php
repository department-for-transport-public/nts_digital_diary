<?php

namespace App\Controller\TravelDiary;

use App\Annotation\Redirect;
use App\Entity\Journey\Journey;
use App\Entity\User;
use App\Repository\Journey\JourneyRepository;
use App\Utility\ConfirmAction\TravelDiary\DeleteJourneyConfirmAction;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/journey/{journeyId}", name="journey")
 * @Entity("journey", expr="repository.findByJourneyId(journeyId)")
 * @Redirect("is_granted('DIARY_KEEPER_WITH_APPROVED_DIARY')", route="traveldiary_dashboard")
 */
class JourneyController extends AbstractController
{
    /**
     * @Route(name="_view")
     * @Template("travel_diary/journey/view.html.twig")
     */
    public function view(UserInterface $user, Journey $journey, JourneyRepository $journeyRepository): array
    {
        $diaryKeeper = $journey->getDiaryDay()->getDiaryKeeper();

        if (!$user instanceof User || $user->getDiaryKeeper() !== $diaryKeeper) {
            throw new NotFoundHttpException();
        }

        return [
            'diaryKeeper' => $diaryKeeper,
            'day' => $journey->getDiaryDay(),
            'journey' => $journey,
            'sharedFromName' => $journeyRepository->getSharedFromName($journey),
            'sharedToNames' => $journeyRepository->getSharedToNames($journey),
        ];
    }

    /**
     * @Route("/delete", name="_delete")
     * @Template("travel_diary/journey/delete.html.twig")
     */
    public function delete(Journey $journey, DeleteJourneyConfirmAction $confirmAction, Request $request)
    {
        return $confirmAction
            ->setSubject($journey)
            ->controller(
                $request,
                $this->generateUrl('traveldiary_dashboard_day', ['dayNumber' => $journey->getDiaryDay()->getNumber()]),
                $this->generateUrl('traveldiary_journey_view', ['journeyId' => $journey->getId()])
            );
    }

    /**
     * @Route("/complete", name="_complete")
     * @Template("travel_diary/journey/delete.html.twig")
     */
    public function markComplete(Journey $journey)
    {
        $journey->setIsPartial(false);
        $this->getDoctrine()->getManager()->flush();

        return new RedirectResponse($this->generateUrl('traveldiary_dashboard_day', ['dayNumber' => $journey->getDiaryDay()->getNumber()]));
    }
}